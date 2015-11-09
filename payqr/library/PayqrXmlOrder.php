<?php


class PayqrXmlOrder {
    
    /**
     *
     * @var class PayqrInvoice
     */
    private $invoice;
    
    
    public function __construct($payr_invoice)
    {
        $this->invoice = $payr_invoice;
    }
    
    public function getXmlProduct()
    {
        $carts = $this->invoice->getCart();
        
        $xml = "";
        
        if(is_array($carts))
        {
            foreach($carts as $cart)
            {
                $xml .= '<variant-id>'.$cart->article.'</variant-id>
                         <quantity>'.$cart->quantity.'</quantity>';
            }
        }
        
        return $xml;
    }
    
    public function changeOrderPayStatus()
    {
        $orderId = $this->invoice->getOrderId();
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
                <order>
                    <id type="integer">'.$orderId.'</id>
                    <financial-status>paid</financial-status>
                </order>';
        
        return $xml;
    }
}
