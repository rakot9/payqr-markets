<?php

class PayqrMessage {
    
    private static $instance;
    private $settings;
    private $invoice;
    
    const ORDERCREATING = "inv.order.creating";
    const ORDERPAID = "inv.paid";
    const ORDERREVERT = "inv.revert";

    private function __construct($settings, $invoice) {
        $this->setSettings($settings);
        $this->setInvoice($invoice);
    }
    
    public static function getInstance($settings, $invoice)
    {
        if(self::$instance instanceof PayqrMessage)
        {
            return self::$instance;
        }
        return new self($settings, $invoice);
    }
    
    private function setSettings($settings)
    {
        $this->settings = $settings;
    }
    
    private function getSettings()
    {
        return $this->settings;
    }
    
    private function setInvoice($invoice)
    {
        $this->invoice = $invoice;
    }
    
    private function getInvoice()
    {
        return $this->invoice;
    }
    
    public function setMessage($invoiceType)
    {
        $prefix_message = "";
        
        switch ($invoiceType)
        {
            case self::ORDERCREATING:
                $prefix_message = "creating";
                break;
            case self::ORDERPAID:
                $prefix_message = "paid";
                break;
            case self::ORDERREVERT:
                $prefix_message = "revert";
                break;
        }
        
        if(!empty($prefix_message))
        {
            $this->getInvoice()->setUserMessage((object)array(
                "article" => 1,
                "text" => isset($this->settings['user_message_order_'.$prefix_message.'_text'])? $this->settings['user_message_order_'.$prefix_message.'_text'] : "",
                "url" => isset($this->settings['user_message_order_'.$prefix_message.'_url'])? $this->settings['user_message_order_'.$prefix_message.'_url'] : "",
                "imageUrl" => isset($this->settings['user_message_'.$prefix_message.'_revert_imageurl'])? $this->settings['user_message_order_'.$prefix_message.'_imageurl'] : ""
            ));
        }
    }
}
