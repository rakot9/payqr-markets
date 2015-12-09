<?php
use frontend\modules\Payqr\models\Market;
use frontend\models\Market as frModelmarket;

class InvoiceHandler 
{
    private $invoice;
    private $settings;
    
    const INSALESFORMAT = "JSON";

    public function __construct(PayqrInvoice $invoice) 
    {
        $this->invoice = $invoice;
        
        $marketObj = new Market();
        
        $this->market = $marketObj->getMarket(PayqrConfig::$merchantID);
        
        $this->settings = json_decode($this->market->getSettings(), true);
    }    
    
    public function createOrder()
    {
        if(!$this->settings) {
            PayqrLog::log("Не смогли получить настройки кнопки, прекращаем работу!");
            return false;
        }
        
        $invoiceId = $this->invoice->getInvoiceId();
        
        /*
         * Отправляем сообщение пользователю
         */
        $this->invoice->setUserMessage((object)array(
            "article" => 1,
            "text" => isset($this->settings['user_message_order_creating_text'])? $this->settings['user_message_order_creating_text'] : "",
            "url" => isset($this->settings['user_message_order_creating_url'])? $this->settings['user_message_order_creating_url'] : "",
            "imageUrl" => isset($this->settings['user_message_order_creating_imageurl'])? $this->settings['user_message_order_creating_imageurl'] : ""
        ));
        
        $result = \frontend\models\InvoiceTable::find()->where(["invoice_id" => $invoiceId])->one();
        
        if($result && isset($result->order_id, $result->amount) && !empty($result->order_id) && !empty($result->amount))
        {
            $ordersId = json_decode($result->order_id, true);
            if(is_array($ordersId) && isset($ordersId['oExternal'], $ordersId['oInternal'])) {
                $this->invoice->setOrderId($ordersId['oExternal']);
                $this->invoice->setAmount($result->amount);
                $this->invoice->setUserData(json_encode(array("orderId" => $ordersId['oInternal'])));
                return true;
            }
        }
        
        $xmlOrder = new PayqrXmlOrder($this->invoice);
        $orderXml = $xmlOrder->getXMLOrder();

        /*
         * Создаем заказ через API InSales (отправляем xml)
         */
        $payqrCURLObject = new PayqrCurl();
        $response = $payqrCURLObject->sendXMLFile(PayqrConfig::$insalesURL . "orders.xml", $orderXml);
        if(!$response) {
            PayqrLog::log("Ответ от сервера InSales не в формате xml");
            return false;
        }
        PayqrLog::log("Ответ от сервера \r\n".$response);

        $xml = new SimpleXMLElement($response);
        $orderResultExternal = $xml->xpath("/order/number");
        $orderResultInternal = $xml->xpath("/order/id");
        $orderResultAmount   = $xml->xpath("/order/order-lines/order-line/total-price");
        
        PayqrLog::log("Получили обработанный XML \r\n".$xml);

        if(!isset($orderResultExternal[0]) || !isset($orderResultInternal[0])) {
            PayqrLog::log("Не смогли получить xml-ответ по созданному заказу!");
            return false;
        }

        $orderIdInternal = (int)$orderResultInternal[0]; PayqrLog::log("Внутренний Id:" . $orderIdInternal);
        $orderIdExternal = (int)$orderResultExternal[0]; PayqrLog::log("Внешний Id:" . $orderIdExternal);
        $this->invoice->setOrderId($orderIdExternal);
        
        $totalPrice = 0;
        while(list(, $price) = each($orderResultAmount)) {
            $totalPrice += round((float)$price,2);
        }
        if(empty($totalPrice)) {
            PayqrLog::log("ОШИБКА! Сумма заказа равна 0!");
            return false;
        }
        $deliveryCased = $this->invoice->getDeliveryCasesSelected();
        if(isset($deliveryCased->amountFrom) && !empty($deliveryCased->amountFrom) && $deliveryCased->amountFrom)
        {
            $totalPrice = (float)$totalPrice + (float) $deliveryCased->amountFrom;
        }
        $this->invoice->setAmount($totalPrice);

        //удаляем строку по условию
        \frontend\models\InvoiceTable::deleteAll(["invoice_id" => $invoiceId]);

        PayqrLog::log(json_encode(array("oInternal" => $orderIdInternal, "oExternal" => $orderIdExternal)));
        $invoiceTable = new \frontend\models\InvoiceTable();
        $invoiceTable->invoice_id = $invoiceId;
        $invoiceTable->order_id = json_encode(array("oInternal" => $orderIdInternal, "oExternal" => $orderIdExternal));
        $invoiceTable->amount = $totalPrice;
        $invoiceTable->save();
            
        $this->invoice->setUserData(json_encode(array("orderId" => $orderIdInternal)));
        
        return;
    }
    
    public function payOrder()
    {
        if(!$this->settings) {
            PayqrLog::log("inv_paid. Не смогли получить настройки кнопки, прекращаем работу!");
            return false;
        }
        
        $result = \frontend\models\InvoiceTable::find()->where(["invoice_id" => $this->invoice->getInvoiceId()])->one();
        
        if(!$result) {
            PayqrLog::log("inv_paid. Не смогли получить информацию о заказе из таблицы invoice_table");
            return false;
        }
        
        if($result && isset($result->is_paid) && !empty((int)$result->is_paid)) {
            PayqrLog::log("inv_paid. Повторный запрос.");
            return true;
        }
        
        $orderIdInternal = $this->getInternalOrderId();
        $orderIdExternal = $this->getExternalOrderId();
        
        if(empty($orderIdInternal)){
            PayqrLog::log("inv_paid. Не смогли получить orderIdInternal");
            return false;
        }
        
        if(!$this->isPaid()) {
            //!!! Перенесено из конца функции т.к. возможно долго приходит ответ от InSales
            \frontend\models\InvoiceTable::updateAll(['is_paid' => 1], 'invoice_id = :invoice_id', [':invoice_id' => $this->invoice->getInvoiceId()]);
            /*
            * Подготавливаем XML для смены статуса заказа
            */
            $xmlOrder = new PayqrXmlOrder($this->invoice);
            $statusPayXml = $xmlOrder->changeOrderPayStatus();
            if(empty($statusPayXml)) {
               PayqrLog::log("inv_paid. Не смогли получить xml-файл");
               return false;
            }
            $payqrCURLObject = new PayqrCurl();
            PayqrLog::log("inv_paid. Изменяем статус заказа. Отправляем xml файл \r\n" . $statusPayXml);
            PayqrLog::log("inv_paid. URL: " . PayqrConfig::$insalesURL . "orders/" . $orderIdInternal . ".xml");
            $response = $payqrCURLObject->sendXMLFile(PayqrConfig::$insalesURL . "orders/" . $orderIdInternal . ".xml", $statusPayXml, 'PUT');
            PayqrLog::log("Получили ответ после изменения статуса оплаты заказа \r\n" . print_r($response, true));
        }
        
        $this->invoice->setUserMessage((object)array(
            "article" => 1,
            "text" => isset($this->settings['user_message_order_paid_text'])? $this->settings['user_message_order_paid_text'] : "",
            "url" => isset($this->settings['user_message_order_paid_url'])? $this->settings['user_message_order_paid_url'] : "",
            "imageUrl" => isset($this->settings['user_message_order_paid_imageurl'])? $this->settings['user_message_order_paid_imageurl'] : ""
        ));
        
        //\frontend\models\InvoiceTable::updateAll(['is_paid' => 1], 'invoice_id = :invoice_id', [':invoice_id' => $this->invoice->getInvoiceId()]);
        
        return true;
    }
    
    public function revertOrder()
    {
        if(!$this->isPaid()) {
            PayqrLog::log("revert. Не можем осуществить возврат,не оплаченного заказа!");
            return false;
        }
        
        $xmlOrder = new PayqrXmlOrder($this->invoice);
        $statusPayXml = $xmlOrder->changeOrderPayStatus("pending", "returned");
        if(empty($statusPayXml)) {
            PayqrLog::log("revert. Не смогли получить xml-файл");
            return false;
        }
        
        $orderInternalId = $this->getInternalOrderId();
        
        if(!$orderInternalId){
            PayqrLog::log("revert. Не смогли получить orderInternalId");
            return false;
        }

        $payqrCURLObject = new PayqrCurl();
        PayqrLog::log("revert. URL: " . PayqrConfig::$insalesURL . "orders/" . $orderInternalId . ".xml");
        $response = $payqrCURLObject->sendXMLFile(PayqrConfig::$insalesURL . "orders/" . $orderInternalId . ".xml", $statusPayXml, 'PUT');
        PayqrLog::log("revert. Получили ответ после изменения статуса возврата заказа \r\n" . print_r($response, true));

        //отправляем сообщение пользователю
        $this->invoice->setUserMessage((object)array(
            "article" => 1,
            "text" => isset($this->settings['user_message_order_revert_text'])? $this->settings['user_message_order_revert_text'] : "",
            "url" => isset($this->settings['user_message_order_revert_url'])? $this->settings['user_message_order_revert_url'] : "",
            "imageUrl" => isset($this->settings['user_message_order_revert_imageurl'])? $this->settings['user_message_order_revert_imageurl'] : ""
        ));
        
        return true;
    }
    
    public function cancelOrder()
    {
        $xmlOrder = new PayqrXmlOrder($this->invoice);
        $statusPayXml = $xmlOrder->changeOrderPayStatus("pending", "declined");
        if(empty($statusPayXml)){
            PayqrLog::log("cancel. Не смогли получить xml-файл");            
            return false;
        }
        
        if($this->isPaid()) {
            PayqrLog::log("cancel. Не можем отменить проплаченный товар");
            return false; 
        }
        
        $orderInternalId = $this->getInternalOrderId();
        if(!$orderInternalId){
            PayqrLog::log("cancel. Не смогли получить orderInternalId");
            return false;
        }
        
        $payqrCURLObject = new PayqrCurl();
        PayqrLog::log("cancel. Отправляем запрос на следующий URL: " . PayqrConfig::$insalesURL . "orders/" . $orderInternalId . ".xml");
        $response = $payqrCURLObject->sendXMLFile(PayqrConfig::$insalesURL . "orders/" . $orderInternalId . ".xml", $statusPayXml, 'PUT');
        PayqrLog::log("cancel. Получили ответ после изменения статуса оплаты заказа\r\n" /*. print_r($response, true)*/);
        
        return true;
    }
    
    public function failOrder()
    {
    }
    
    public function setDeliveryCases()
    {
        $invoice_id = $this->invoice->getInvoiceId();
        
        PayqrLog::log("Получили InvoiceId:" . $invoice_id);

        $result = \frontend\models\InvoiceTable::find()->where(["invoice_id" => $invoice_id])->one();
        
        $deliveryData = json_decode($result->data);
        
        if($deliveryData)
        {
            //проверяем данные в формате json, но в любом случае наличие данных говорит, о том, что запрос уже был
            PayqrLog::log("setDeliveryCases. Уже иммем все необходимые данные, возвращаем их!");
            $this->invoice->setDeliveryCases($deliveryData);
            return true;
        }
        //
        PayqrLog::log("setDeliveryCases. Первый  запрос, сохраняем данные!");
        $invoiceTable = new \frontend\models\InvoiceTable();
        $invoiceTable->invoice_id = $invoice_id;
        $invoiceTable->data = "";
        $invoiceTable->save();
        
        $payqrDelivery = $this->invoice->getDelivery();

        if(empty($payqrDelivery))
        {
            return array();
        }
        
        //проверяем xml на валидность
        libxml_use_internal_errors(true);

        //Получаем способы доставки через запрос к API InSales
        $payqrCurl = new PayqrCurl();
        
        //вначале производим получение всех способов оплаты, которые присутствуют в системе
        $responsePayqmetsXML = $payqrCurl->sendXMLFile(PayqrConfig::$insalesURL . "payment_gateways.xml", "", "GET");
        PayqrLog::log(print_r($responsePayqmetsXML, true));
        
        $elem = simplexml_load_string($responsePayqmetsXML);
        
        if($elem == false)
        {
            //Не смогли получить информацию о способах доставки
            PayqrLog::log("Не смогли получить информацию о способах доставки \r\n" . $responsePayqmetsXML);
            return array();
        }
        
        //производм разбор xml
        $xml = new SimpleXMLElement($responsePayqmetsXML);
        $paymentsVariants1 = $xml->xpath("/objects/object");
        
        
        $id_payqr_payment = 0;
        
        foreach($paymentsVariants1 as $payment)
        {
            if( strpos(strtolower((string)$payment->title), "payqr") !== false)
            {
                $id_payqr_payment = (int)$payment->id;
                break;
            }
        }
        
        if(!$id_payqr_payment)
        {
            $paymentsVariants = $xml->xpath("/payment-gateway-customs/payment-gateway-custom");
            
            foreach($paymentsVariants as $payment)
            {
                if( strpos(strtolower((string)$payment->title), "payqr") !== false)
                {
                    $id_payqr_payment = (int)$payment->id;
                    break;
                }
            }
        }
        
        if(!$id_payqr_payment)
        {
            //не смогли получить платежную систему
            PayqrLog::log("Не смогли получить способы оплаты");
            return array();
        }
        
        PayqrLog::log("Получили id способы оплаты " . $id_payqr_payment);
        
        //получаем способы доставки
        $responsedeliveriesXML = $payqrCurl->sendXMLFile(PayqrConfig::$insalesURL . "delivery_variants.xml", "", "GET");
        
        PayqrLog::log(print_r($responsedeliveriesXML, true));
        
        $elem = simplexml_load_string($responsedeliveriesXML);
        
        if($elem == false)
        {
            //Не смогли получить информацию о способах доставки
            PayqrLog::log("Не смогли получить информацию о способах доставки \r\n" . $responsedeliveriesXML);
            return array();
        }
        
        //производм разбор xml
        $xml = new SimpleXMLElement($responsedeliveriesXML);
        
        //получаем OrderId-внешний идентификатор
        $deliveryVariants = $xml->xpath("/delivery-variant-fixeds/delivery-variant-fixed");
        
        if(empty($deliveryVariants))
        {
            //не смогли получить варианты доставок
            PayqrLog::log("Не смогли получить варианты доставок");
            return false;
        }
        
        $i = 1;

        foreach($deliveryVariants as $delivery)
        {
            // получаем 
            $isIvertedCity = false;

            //PayqrLog::log(print_r($delivery, true));
            $deliveryPayqments = $delivery->xpath("payment-delivery-variants/payment-delivery-variant");
            //PayqrLog::log(print_r($deliveryPayqments, true));
            
            if(empty($deliveryPayqments))
            {
                continue;
            }

            if(isset($delivery->inverted) && $delivery->inverted == "true")
            {
                $isIvertedCity = true;
            }

            //PayqrLog::log("Нашли варианты оплаты для данной доставки");
            
            foreach ($deliveryPayqments as $deliveryPayment)
            {
                if((int)$deliveryPayment->{"payment-gateway-id"} == $id_payqr_payment)
                {
                    //Проверяем теперь город, для которого разрешена доставка
                    $deliveryLocations = $delivery->xpath("delivery-locations/delivery-location");
                    
                    if(!empty($deliveryLocations))
                    {
                        foreach($deliveryLocations as $deliveryLocation)
                        {
                            if((isset($payqrDelivery->city) && !empty($payqrDelivery->city) && 
                                (!$isIvertedCity && strtolower((string)$deliveryLocation->city) == strtolower($payqrDelivery->city))))
                            {
                                $delivery_cases[] = array(
                                    'article' => (int)$delivery->id,
                                    'number' => $i++,
                                    'name' => (string)$delivery->title,
                                    'description' => strip_tags((string)$delivery->description),
                                    'amountFrom' => round((float)$delivery->price, 2),
                                    'amountTo' => round((float)$delivery->price, 2)
                                );
                            }

                            if(isset($payqrDelivery->city) && !empty($payqrDelivery->city) && 
                                ($isIvertedCity && strtolower((string)$deliveryLocation->city) != strtolower($payqrDelivery->city)))
                            {
                                $delivery_cases[] = array(
                                    'article' => (int)$delivery->id,
                                    'number' => $i++,
                                    'name' => (string)$delivery->title,
                                    'description' => strip_tags((string)$delivery->description),
                                    'amountFrom' => round((float)$delivery->price, 2),
                                    'amountTo' => round((float)$delivery->price, 2)
                                );
                            }
                        }
                    }
                    else
                    {
                        $delivery_cases[] = array(
                            'article' => (int)$delivery->id,
                            'number' => $i++,
                            'name' => (string)$delivery->title,
                            'description' => strip_tags((string)$delivery->description),
                            'amountFrom' => round((float)$delivery->price, 2),
                            'amountTo' => round((float)$delivery->price, 2)
                        );
                    }
                }
            }
        }
        
        //PayqrLog::log("Передаем варианты доставок");
        //PayqrLog::log(print_r($delivery_cases, true));
        
        \frontend\models\InvoiceTable::updateAll(['data' => json_encode($delivery_cases)], 'invoice_id = :invoice_id', [':invoice_id' => $invoice_id]);
        
        $this->invoice->setDeliveryCases($delivery_cases);
    }
    
    public function setPickPoints()
    {
    }
    
    private function getInternalOrderId()
    {
        $orderIdInternal = 0;
        
        $result = \frontend\models\InvoiceTable::find()->where(["invoice_id" => $this->invoice->getInvoiceId()])->one();
        
        if($result) {
            $ordersId = json_decode($result->order_id, true);
            
            if(is_array($ordersId))
                $orderIdInternal = isset($ordersId['oInternal'])? $ordersId['oInternal'] : 0;

            if(!empty($orderIdInternal))
                return $orderIdInternal;
        }

        if(empty($orderIdInternal)) {
            $userData = json_decode($this->invoice->getUserData());
            if(isset($userData->orderId) && !empty($userData->orderId)) {
                return $userData->orderId;
            }
            else{
                return null;
            }
        }
        return null;
    }
    
    private function getExternalOrderId()
    {
        $orderIdExternal = 0;
        
        $result = \frontend\models\InvoiceTable::find()->where(["invoice_id" => $this->invoice->getInvoiceId()])->one();
        
        if($result) {
            $ordersId = json_decode($result->order_id, true);
            
            if(is_array($ordersId))
                $orderIdExternal = isset($ordersId['oExternal'])? $ordersId['oExternal'] : 0;

            if(!empty($orderIdExternal))
                return $orderIdExternal;
        }
        return null;
    }
    
    private function isPaid()
    {
        $result = \frontend\models\InvoiceTable::find()->where(["invoice_id" => $this->invoice->getInvoiceId()])->one();
        
        if($result && isset($result->is_paid) && !empty((int)$result->is_paid))
        {
            return true;
        }
        return false;
    }
}