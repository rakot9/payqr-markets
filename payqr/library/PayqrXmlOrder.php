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
    
    public function changeOrderPayStatus($financeStatus = "paid", $fulFillMent = "accepted")
    {
        $userData = $this->invoice->getUserData();
        
        $userData = json_decode($userData);
        
        if(isset($userData->orderId) && !empty($userData->orderId))
        {
            $xml = '<?xml version="1.0" encoding="UTF-8"?>
                    <order>
                        <id type="integer">'.(int)$userData->orderId.'</id>
                        <financial-status>'.$financeStatus.'</financial-status>
                        <fulfillment-status>'.$fulFillMent.'</fulfillment-status>
                    </order>';

            return $xml;
        }
        return null;
    }
}
