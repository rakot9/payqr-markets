<?php

class OrderTransport {
    
    /**
     * SingleTone variable
     * @var OrderTransport $instance
     */
    private static $instance;
    
    private function __construct() {}
    
    public static function getInstance()
    {
        if(self::$instance instanceof OrderTransport)
        {
            return self::$instance;
        }
        return new self();
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

            return false;
        }
        PayqrLog::log("Ответ от сервера \r\n" . $responceXML);
        
        return $responceXML;
    }
}