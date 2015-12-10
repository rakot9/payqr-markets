<?php


class PayqrXmlOrder {
    
    /**
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
                $xml .= '<order-line-attributes>' . PHP_EOL . 
                        '<variant-id>'.$cart->article.'</variant-id>' . PHP_EOL . 
                        '<quantity>'.$cart->quantity.'</quantity>' . PHP_EOL . 
                        '</order-line-attributes>';
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
    
    public function getXMLOrder()
    {
        $customer = $this->invoice->getCustomer();
        $shipping = $this->invoice->getDeliveryCasesSelected();
        
        return '<?xml version="1.0" encoding="UTF-8"?>
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
                                '.$this->getXmlProduct().'
                        </order-lines-attributes>
                    </order>';
    }
    
    public function createOrder()
    {
        
    }
}
