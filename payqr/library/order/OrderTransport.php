<?php

class OrderTransport {
    
    /**
     * SingleTone variable
     * @var OrderTransport $instance
     */
    private static $instance;
    private $invoiceId;
    
    private function __construct($invoiceId) {
        $this->invoiceId = $invoiceId;
    }
    
    public static function getInstance($invoiceId)
    {
        if(self::$instance instanceof OrderTransport)
        {
            return self::$instance;
        }
        return new self($invoiceId);
    }
    
    /**
     * 
     * @param type $xml
     * @return boolean
     */
    public function createOrder($xml)
    {
        $payqrCURLObject = new PayqrCurl();
        
        $responceXML = $payqrCURLObject->sendXMLFile(PayqrConfig::$insalesURL . "orders.xml", $xml);
        
        if(!$responceXML) 
        {
            PayqrLog::log("Ответ от сервера InSales не в формате xml");
            
            //Помечаем ответ от InSales, как ошибочный
            \frontend\models\InvoiceTable::updateAll(['order_request' => -1], 'invoice_id = :invoice_id', [':invoice_id' => $this->invoiceId]);

            return false;
        }
        //PayqrLog::log("Ответ от сервера \r\n" . $responceXML);
        
        return $responceXML;
    }
}