<?php


class PayqrXmlOrder {
    
    /**
     *
     * @var class PayqrInvoice
     */
    private $invoice;
    
    
    public function __contruct($payr_invoice)
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
                $xml .= '<product-id>'.$cart->article.'</product-id>
                         <quantity>'.$cart->quantity.'</quantity>';
            }
        }
        
        return $xml;
    }
}
