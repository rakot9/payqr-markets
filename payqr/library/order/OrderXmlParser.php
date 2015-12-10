<?php

class OrderXmlParser {
    
    /**
     * SingleTone variable
     * @var OrderTransport $instance
     */
    private static $instance;
    
    private $orderXML;
    
    private function __construct($orderXML) {
        $this->orderXML = $orderXML;
    }
    
    /**
     * 
     * @param type $orderXML
     * @return \self
     */
    public static function getInstance($orderXML)
    {
        if(self::$instance instanceof OrderXmlParser)
        {
            return self::$instance;
        }
        return new self($orderXML);
    }
    
    /**
     * 
     * @return \SimpleXMLElement
     */
    public function parseOrderXML()
    {
        return new SimpleXMLElement($this->orderXML);
    }
    
    /**
     * Возвращает внешний идентификатор
     * @return type
     */
    public function getExtId()
    {
        $_oId = $this->parseOrderXML()->xpath("/order/number");
        return $_oId[0]? (int)$_oId[0] : null;
    }
    
    /**
     * Возвращает внутренний идентификатор
     * @return int | null
     */
    public function getIntId()
    {
        $_oId = $this->parseOrderXML()->xpath("/order/id");
        return $_oId[0]? (int)$_oId[0] : null;
    }
    
    /**
     * 
     * @return boolean | float
     */
    public function getTotal()
    {
        $orderResultAmount = $this->parseOrderXML()->xpath("/order/order-lines/order-line/total-price");
        
        $totalPrice = 0;
        while(list(, $price) = each($orderResultAmount)) {
            $totalPrice += round((float)$price,2);
        }
        if(empty($totalPrice)) {
            PayqrLog::log("ОШИБКА! Сумма заказа равна 0!");
            return false;
        }
        return $totalPrice;
    }
}
