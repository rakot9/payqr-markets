<?php
use frontend\modules\Payqr\models\Market;
use frontend\models\Market as frModelmarket;

class InvoiceHandler 
{
    private $invoice;
    
    public function __construct(PayqrInvoice $invoice) 
    {
        $this->invoice = $invoice;
    }    
    
    /*
    * Код выполнен, когда интернет-сайт получит уведомление от PayQR о необходимости создания заказа в учетной системе интернет-сайта.
    * Это означает, что покупатель приблизился к этапу оплаты, а, значит, интернет-сайту нужно создать заказ в своей учетной системе, если такой заказ еще не создан, и вернуть в ответе PayQR значение orderId в объекте "Счет на оплату", если orderId там еще отсутствует.
    *
    * $this->invoice содержит объект "Счет на оплату" (подробнее об объекте "Счет на оплату" на https://payqr.ru/api/ecommerce#invoice_object)
    *
    * Ниже можно вызвать функции своей учетной системы, чтобы особым образом отреагировать на уведомление от PayQR о событии invoice.order.creating.
    *
    * Важно: после уведомления от PayQR об invoice.order.creating в содержании конкретного объекта "Счет на оплату" должен быть обязательно заполнен параметр orderId (если он не заполнялся на уровне кнопки PayQR). По правилам PayQR оплата заказа не может быть начата до тех пор, пока в счете не появится номер заказа (orderId). Если интернет-сайт не ведет учет заказов по orderId, то на данном этапе можно заполнить параметр orderId любым случайным значением, например, текущими датой и временем. Также важно, что invoice.order.creating является первым и последним этапом, когда интернет-сайт может внести коррективы в параметры заказа (например, откорректировать названия позиций заказа).
    *
    * Часто используемые методы на этапе invoice.order.creating:
    *
    * * Получаем объект адреса доставки из "Счета на оплату"
    * $this->invoice->getDelivery();
    * * вернет:
    * "delivery": { "country": "Россия", "region": "Москва", "city": "Москва", "zip": "115093", "street": "Дубининская ул.", "house": "80", "comment": "У входа в автосалон Хонда", }
    *
    * * Получаем объект содержания корзины из "Счета на оплату"
    * $this->invoice->getCart();
    * * вернет:
    * [{ "article": "5675657", "name": "Товар 1", "imageUrl": "http://goods.ru/item1.jpg", "quantity": 5, "amount": 19752.25 }, { "article": "0", "name": "PROMO акция", "imageUrl": "http://goods.ru/promo.jpg", }]
    *
    * * Обновляем содержимое корзины в объекте "Счет на оплату" в PayQR
    * $this->invoice->setCart($cartObject);
    *
    * * Получаем объект информации о покупателе из "Счета на оплату"
    * $this->invoice->getCustomer();
    * * вернет:
    * { "firstName": "Иван", "lastName": "Иванов", "phone": "+79111111111", "email": "test@user.com" }
    *
    * * Устанавливаем orderId из учетной системы интернет-сайта в объекте "Счет на оплату" в PayQR
    * $this->invoice->setOrderId($orderId);
    *
    * * Получаем сумму заказа из "Счета на оплату"
    * $this->invoice->getAmount();
    *
    * * Изменяем сумму заказа в объекте "Счет на оплату" в PayQR (например, уменьшаем сумму, чтобы применить скидку)
    * $this->invoice->setAmount($amount);
    *
    * * Если по каким-то причинам нам нужно отменить этот заказ сейчас (работает только при обработке события invoice.order.creating)
    * $this->invoice->cancelOrder(); вызов этого метода отменит заказ
    */
    public function createOrder()
    {
        //получаем информацию о настройках кнопки
        $marketObj = new Market();
        $this->market = $marketObj->getMarket(PayqrConfig::$merchantID);
        
        if(!isset($this->market->settings))
        {
            PayqrLog::log("Не смогли получить настройки кнопки, прекращаем работу!");
            return false;
        }
        
        $settings = json_decode($this->market->getSettings(), true);
        
        $xmlOrder = new PayqrXmlOrder($this->invoice);
        
        $customer = $this->invoice->getCustomer();
        $shipping = $this->invoice->getDeliveryCasesSelected();
        
        PayqrLog::log(print_r($customer, true));
        PayqrLog::log(print_r($shipping, true));
        
        //Производим проверку на наличие invoice_id
        $invoice_id = $this->invoice->getInvoiceId();

        $result = \frontend\models\InvoiceTable::find(["invoice_id" => $invoice_id])->one();
        
        PayqrLog::log(print_r($result, true));
        
        if($result && isset($result->order_id, $result->amount) && !empty($result->order_id) && !empty($result->amount))
        {
            $this->invoice->setOrderId($result->order_id);
            $this->invoice->setAmount($result->amount);
        }
        else
        {
            //Формируем xml с запросом на создание заказа
            $orderXml = '<?xml version="1.0" encoding="UTF-8"?>
                        <order>
                            <force type="boolean">true</force>
                            <shipping-address>
                                <address>'.(isset($shipping->city)?           $shipping->city.' ':'').
                                           (isset($shipping->street)?   'Ул. '.$shipping->street.' ':'').
                                           (isset($shipping->house)?    'Д. '.$shipping->house.' ':'').
                                           (isset($shipping->unit)?     'Корп. '.$shipping->unit.' ':'').
                                           (isset($shipping->building)? 'Стр. '.$shipping->building.' ':'').
                                           (isset($shipping->flat)?     'Кв. '.$shipping->flat.' ':'').
                                           (isset($shipping->hallway)?  'Под. '.$shipping->hallway.' ':'').
                                           (isset($shipping->floor)?    'Эт. ' .$shipping->floor.' ':'').
                                           (isset($shipping->intercom)? 'Дмфн.' . $shipping->intercom.' ':'').
                                           (isset($shipping->comment)?  $shipping->comment.' ':'').
                                '</address>
                                <country>RU</country>
                            '. (isset($shipping->city)? '<city>'.$shipping->city.'</city>' : '') .'
                            '. (isset($shipping->zip)? '<zip>'.$shipping->zip.'</zip>' : '<zip nil="true"/>') .'
                            '. (isset($customer->firstName)? '<name>'.$customer->firstName.'</name>':'<name nil="true"/>') .'
                            '. (isset($customer->phone)? '<phone>'.$customer->phone.'</phone>':'<phone nil="true"/>') .'
                                <state nil="true"/>
                            </shipping-address>
                            <client>
                            '. (isset($customer->email)? '<email>'.$customer->email.'</email>':'') .'
                            '. (isset($customer->phone)? '<phone>'.$customer->phone.'</phone>':'') .'
                            '. (isset($customer->firstName)? '<name>'.$customer->firstName.'</name>':'') .'
                            '. (isset($customer->middleName)? '<middlename>'.$customer->middleName.'</middlename>':'') .'
                            '. (isset($customer->lastName)? '<surname>'.$customer->lastName.'</surname>':'') .'
                            </client>
                            <order-lines-attributes type="array">
                                <order-line-attributes>
                                    '.$xmlOrder->getXmlProduct().'
                                </order-line-attributes>
                            </order-lines-attributes>
                        </order>';

            PayqrLog::log("Наш ответ\r\n" . $orderXml);

            //производим отправку данных на сервер
            $payqrCURLObject = new PayqrCurl();

            PayqrLog::log("Отправляем информацию о создании заказа! " . PayqrConfig::$insalesURL . "orders.xml");

            $response = $payqrCURLObject->sendXMLFile(PayqrConfig::$insalesURL . "orders.xml", $orderXml);

            if(!$response)
            {
                PayqrLog::log("Ответ от сервера InSales не в формате xml");
                return false;
            }

            PayqrLog::log("Получили ответ от сервера в виде XML-файла \r\n".$response);


            //производм разбор xml
            $xml = new SimpleXMLElement($response);

            //получаем OrderId-внешний идентификатор
            $orderResultExternal = $xml->xpath("/order/number");

            if(!isset($orderResultExternal[0]))
            {
                return false;
            }

            //получаем OrderId-внешний идентификатор

            $orderResultInternal = $xml->xpath("/order/id");

            if(!isset($orderResultInternal[0]))
            {
                return false;
            }

            $orderIdInternal = (int)$orderResultInternal[0]; PayqrLog::log("Внутренний Id:" . $orderIdInternal);
            $orderIdExternal = (int)$orderResultExternal[0]; PayqrLog::log("Внешний Id:" . $orderIdExternal);

            //Устанавливаем номер заказ
            $this->invoice->setOrderId($orderIdExternal);

            //Устаналиваем стоимость заказа
            $orderAmountResult = $xml->xpath("/order/order-lines/order-line/total-price");

            $totalPrice = 0;

            while(list(, $price) = each($orderAmountResult))
            {
                $totalPrice += round((float)$price,2);
            }

            $this->invoice->setAmount($totalPrice);

            $this->invoice->setUserData(json_encode(array("orderId" => $orderIdInternal)));
            
            //удаляем строку по условию
            \frontend\models\InvoiceTable::deleteAll(["invoice_id" => $invoice_id]);
            
            $invoiceTable = new \frontend\models\InvoiceTable();
            $invoiceTable->invoice_id = $invoice_id;
            $invoiceTable->order_id = $orderIdInternal;
            $invoiceTable->amount = $totalPrice;
            $invoiceTable->save();
        }
        
        //отправляем сообщение пользователю
        $this->invoice->setUserMessage((object)array(
            "article" => 1,
            "text" => isset($settings['user_message_order_creating_text'])? $settings['user_message_order_creating_text'] : "",
            "url" => isset($settings['user_message_order_creating_url'])? $settings['user_message_order_creating_url'] : "",
            "imageUrl" => isset($settings['user_message_order_creating_imageurl'])? $settings['user_message_order_creating_imageurl'] : ""
        ));
    }
    
    /**
    * Код будет выполнен, когда интернет-сайт получит уведомление от PayQR об успешной оплате конкретного заказа.
    * Это означает, что PayQR успешно списал запрошенную интернет-сайтом сумму денежных средств с покупателя и перечислит ее интернет-сайту в ближайшее время, интернет-сайту нужно исполнять свои обязанности перед покупателем, т.е. доставлять товар или оказывать услугу. 
    *
    * $this->invoice содержит объект "Счет на оплату" (подробнее об объекте "Счет на оплату" на https://payqr.ru/api/ecommerce#invoice_object)
    *
    * Ниже можно вызвать функции своей учетной системы, чтобы особым образом отреагировать на уведомление от PayQR о событии invoice.paid.
    *
    * Получить orderId из объекта "Счет на оплату", по которому произошло событие, можно через $this->invoice->getOrderId();
    *
    * Важно: несмотря на то, что заказ создается на этапе получения уведомления о событии invoice.order.creating, крайне рекомендуется валидировать все содержание заказа и после получения уведомления о событии invoice.paid. А в случае, когда запрос адреса доставки у покупателя на уровне кнопки PayQR, настроен на рекомендательный режим (спрашивать после оплаты/спрашивать необязательно), то не просто рекомендуется, а обязательно, так как объект "Счет на оплату" на этапе invoice.paid будет содержать в себе расширенные окончательные данные, которых не было на invoice.order.creating. Если по результатам проверки данных из invoice.paid обнаружатся какие-то критичные расхождения (например, сумма заказа из объекта "Счет на оплату" не сходится с суммой из соответствующего заказа), можно сразу послать запрос в PayQR на отмену счету после его оплаты (возврат денег).
    */
    public function payOrder()
    {
        $invoice_id = $this->invoice->getInvoiceId();

        $result = \frontend\models\InvoiceTable::find(["invoice_id" => $invoice_id])->one();
        
        if(!$result)
        {
            return false;
        }
        
        if($result && isset($result->is_paid) && !empty($result->is_paid))
        {
            return true;
        }
        
        //отправляем сообщение об успешности оплаты заказ
        $xmlOrder = new PayqrXmlOrder($this->invoice);
        
        $statusPayXml = $xmlOrder->changeOrderPayStatus();
        
        if(empty($statusPayXml))
        {
            PayqrLog::log("Не смогли получить xml-файл");
            
            return false;
        }
        
        PayqrLog::log("Изменяем статус заказа. Отправляем xml файл");
        
        PayqrLog::log($statusPayXml);
        
        //производим отправку данных на сервер
        $payqrCURLObject = new PayqrCurl();
        
        $userData = $this->invoice->getUserData();
        
        $userData = json_decode($userData);
        
        if(isset($userData->orderId) && !empty($userData->orderId))
        {
            PayqrLog::log("Отправляем запрос на следующий URL: " . PayqrConfig::$insalesURL . "orders/" . $userData->orderId . ".xml");
            
            $response = $payqrCURLObject->sendXMLFile(PayqrConfig::$insalesURL . "orders/" . $userData->orderId . ".xml", $statusPayXml, 'PUT');
        
            PayqrLog::log("Получили ответ после изменения статуса оплаты заказа");
        
            PayqrLog::log(print_r($response, true));
        }
        
        //отправляем сообщение пользователю
        $this->invoice->setUserMessage((object)array(
            "article" => 1,
            "text" => isset($settings['user_message_order_paid_text'])? $settings['user_message_order_paid_text'] : "",
            "url" => isset($settings['user_message_order_paid_url'])? $settings['user_message_order_paid_url'] : "",
            "imageUrl" => isset($settings['user_message_order_paid_imageurl'])? $settings['user_message_order_paid_imageurl'] : ""
        ));
        
        \frontend\models\InvoiceTable::updateAll(['is_paid' => 1], 'invoice_id = :invoice_id', [':invoice_id' => $invoice_id]);
    }
    
    /*
    * Код будет выполнен, когда интернет-сайт получит уведомление от PayQR о полной отмене счета (заказа) после его оплаты.
    * Это означает, что посредством запросов в PayQR интернет-сайт либо одной полной отменой, либо несколькими частичными отменами вернул всю сумму денежных средств по конкретному счету (заказу).
    *
    * $this->invoice содержит объект "Счет на оплату" (подробнее об объекте "Счет на оплату" на https://payqr.ru/api/ecommerce#invoice_object)
    *
    * Ниже можно вызвать функции своей учетной системы, чтобы особым образом отреагировать на уведомление от PayQR о событии invoice.reverted.
    *
    * Получить orderId из объекта "Счет на оплату", по которому произошло событие, можно через $this->invoice->getOrderId();
    */ 
    public function revertOrder()
    {
        PayqrLog::log('revertOrder()');
        
        //в этом запросе просто производим изменение статуса заказа
        $this->cancelOrder();
        
        //отправляем сообщение пользователю
        $this->invoice->setUserMessage((object)array(
            "article" => 1,
            "text" => isset($settings['user_message_order_revert_text'])? $settings['user_message_order_revert_text'] : "",
            "url" => isset($settings['user_message_order_revert_url'])? $settings['user_message_order_revert_url'] : "",
            "imageUrl" => isset($settings['user_message_order_revert_imageurl'])? $settings['user_message_order_revert_imageurl'] : ""
        ));
    }
    
    /*
    * Код будет выполнен, когда интернет-сайт получит уведомление от PayQR об отмене счета (заказа) до его оплаты.
    * Это означает, что либо вышел срок оплаты счета (заказа), либо покупатель отказался от оплаты счета (заказа), либо PayQR успешно обработал запрос в PayQR от интернет-сайта об отказе от счета (заказа) до его оплаты покупателем.
    *
    * $this->invoice содержит объект "Счет на оплату" (подробнее об объекте "Счет на оплату" на https://payqr.ru/api/ecommerce#invoice_object)
    *
    * Ниже можно вызвать функции своей учетной системы, чтобы особым образом отреагировать на уведомление от PayQR о событии invoice.cancelled.
    *
    * Получить orderId из объекта "Счет на оплату", по которому произошло событие, можно через $this->invoice->getOrderId();
    */
    public function cancelOrder()
    {
        //производим изменения статуса заказа на "Отменен"
        $xmlOrder = new PayqrXmlOrder($this->invoice);
        
        $statusPayXml = $xmlOrder->changeOrderPayStatus("pending", "declined");
        
        if(empty($statusPayXml))
        {
            PayqrLog::log("Не смогли получить xml-файл");            
            return false;
        }
        
        PayqrLog::log("Изменяем статус заказа. Отправляем xml файл");
        
        PayqrLog::log($statusPayXml);
        
        //производим отправку данных на сервер
        $payqrCURLObject = new PayqrCurl();
        
        $userData = $this->invoice->getUserData();
        
        $userData = json_decode($userData);
        
        if(isset($userData->orderId) && !empty($userData->orderId))
        {
            PayqrLog::log("Отправляем запрос на следующий URL: " . PayqrConfig::$insalesURL . "orders/" . $userData->orderId . ".xml");
            
            $response = $payqrCURLObject->sendXMLFile(PayqrConfig::$insalesURL . "/" . $userData->orderId . ".xml", $statusPayXml, 'PUT');
        
            PayqrLog::log("Получили ответ после изменения статуса оплаты заказа");
        
            PayqrLog::log(print_r($response, true));
        }
    }
    
    /*
    * Код будет выполнен, когда интернет-сайт получит уведомление от PayQR о сбое в совершении покупки (завершении операции).
    * Это означает, что что-то пошло не так в процессе совершения покупки (например, интернет-сайт не ответил во время на уведомление от PayQR), поэтому операция прекращена.
    *
    * $this->invoice содержит объект "Счет на оплату" (подробнее об объекте "Счет на оплату" на https://payqr.ru/api/ecommerce#invoice_object)
    *
    * Ниже можно вызвать функции своей учетной системы, чтобы особым образом отреагировать на уведомление от PayQR о событии invoice.failed.
    *
    * Получить orderId из объекта "Счет на оплату", по которому произошло событие, можно через $this->invoice->getOrderId();
    */
    public function failOrder()
    {
        
    }
    
    /**
    * Код в этом файле будет выполнен, когда интернет-сайт получит уведомление от PayQR о необходимости предоставить покупателю способы доставки конкретного заказа.
    * Это означает, что интернет-сайт на уровне кнопки PayQR активировал этап выбора способа доставки покупателем, и сейчас покупатель дошел до этого этапа.
    *
    * $this->invoice содержит объект "Счет на оплату" (подробнее об объекте "Счет на оплату" на https://payqr.ru/api/ecommerce#invoice_object)
    *
    * Ниже можно вызвать функции своей учетной системы, чтобы особым образом отреагировать на уведомление от PayQR о событии invoice.deliverycases.updating.
    *
    * Важно: на уведомление от PayQR о событии invoice.deliverycases.updating нельзя реагировать как на уведомление о создании заказа, так как иногда оно будет поступать не от покупателей, а от PayQR для тестирования доступности функционала у конкретного интернет-сайта, т.е. оно никак не связано с реальным формированием заказов. Также важно, что в ответ на invoice.deliverycases.updating интернет-сайт может передать в PayQR только содержимое параметра deliveryCases объекта "Счет на оплату". Передаваемый в PayQR от интернет-сайта список способов доставки может быть многоуровневым.
    *
    * Пример массива способов доставки:
    * $delivery_cases = array(
    *          array(
    *              'article' => '2001',
    *               'number' => '1.1',
    *               'name' => 'DHL',
    *               'description' => '1-2 дня',
    *               'amountFrom' => '0',
    *               'amountTo' => '70',
    *              ),
    *          .....
    *  );
    * $this->invoice->setDeliveryCases($delivery_cases);
    */
    public function setDeliveryCases()
    {
        $invoice_id = $this->invoice->getInvoiceId();

        $result = \frontend\models\InvoiceTable::find(["invoice_id" => $invoice_id])->one();
        
        if(!$result)
        {
            $invoiceTable = new \frontend\models\InvoiceTable();
            $invoiceTable->invoice_id = $invoice_id;
            $invoiceTable->save();
        }
        
        $payqrDelivery = $this->invoice->getDelivery();
        
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
        
        $paymentsVariants = $xml->xpath("/payment-gateway-customs/payment-gateway-custom");
        $paymentsVariants1 = $xml->xpath("/objects/object");
        
        if(empty($paymentsVariants1) && empty($paymentsVariants))
        {
            //не смогли получить варианты доставок
            PayqrLog::log("Не смогли получить способы оплаты");
            return array();
        }
        
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
            PayqrLog::log(print_r($delivery, true));
            $deliveryPayqments = $delivery->xpath("payment-delivery-variants/payment-delivery-variant");
            PayqrLog::log(print_r($deliveryPayqments, true));
            
            if(!empty($deliveryPayqments))
            {
                PayqrLog::log("Нашли варианты оплаты для данной доставки");
                
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
                                if(!empty($payqrDelivery) && 
                                        (isset($payqrDelivery->city) && !empty($payqrDelivery->city) && strtolower((string)$deliveryLocation->city) == strtolower($payqrDelivery->city)) )
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
        }
        
        PayqrLog::log("Передаем варианты доставок");
        PayqrLog::log(print_r($delivery_cases, true));
        
        $this->invoice->setDeliveryCases($delivery_cases);
    }
    
    /*
    * Код в этом файле будет выполнен, когда интернет-сайт получит уведомление от PayQR о необходимости предоставить покупателю пункты самовывоза конкретного заказа.
    * Это означает, что интернет-сайт на уровне кнопки PayQR активировал этап выбора пункта самовывоза покупателем, и сейчас покупатель дошел до этого этапа.
    *
    * $this->invoice содержит объект "Счет на оплату" (подробнее об объекте "Счет на оплату" на https://payqr.ru/api/ecommerce#invoice_object)
    *
    * Ниже можно вызвать функции своей учетной системы, чтобы особым образом отреагировать на уведомление от PayQR о событии invoice.pickpoints.updating.
    *
    * Важно: на уведомление от PayQR о событии invoice.pickpoints.updating нельзя реагировать как на уведомление о создании заказа, так как иногда оно будет поступать не от покупателей, а от PayQR для тестирования доступности функционала у конкретного интернет-сайта, т.е. оно никак не связано с реальным формированием заказов. Также важно, что в ответ на invoice.pickpoints.updating интернет-сайт может передать в PayQR только содержимое параметра pickPoints объекта "Счет на оплату". Передаваемый в PayQR от интернет-сайта список пунктов самовывоза может быть многоуровневым.
    *
    * Пример массива способов доставки:
    * $pick_points_cases = array(
    *          array(
    *              'article' => '1001',
    *               'number' => '1.1',
    *               'name' => 'Наш пункт самовывоза 1',
    *               'description' => 'с 10:00 до 22:00',
    *               'amountFrom' => '90',
    *               'amountTo' => '140',
    *              ),
    *          .....
    *  );
    * $this->invoice->setPickPointsCases($pick_points_cases);
    */
    public function setPickPoints()
    {
        
    }
}
