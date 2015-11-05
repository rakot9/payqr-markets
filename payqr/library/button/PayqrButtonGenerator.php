<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Класс используется для генерации кнопки в цмс
 *
 * @author 1
 */
class PayqrButtonGenerator 
{
    private $scenario = "buy";
    
    private $type_cart = "cart";
    private $type_product = "product";
    private $type_category = "category";
    
  
    public function showCartButton()
    {
        $products = array();
        return $this->get_button_html($this->scenario, $products, $this->type_cart);
    }

    public function showProductButton()
    {
        $products = array();
        return $this->get_button_html($this->scenario, $products, $this->type_product);
    }

    public function showCategoryButton($model)
    {
        $products = array();
        return $this->get_button_html($this->scenario, $products, $this->type_category);
    }
    
    private function get_button_html($scenario, $products, $type)
    {
        $data = $this->get_data($scenario, $products, $type);
        $html = "<button";
        foreach($data as $attr=>$value)
        {
            if(is_array($value))
            {
                $value = implode(" ", $value);
            }
            if(!empty($value))
            {
                $html .= " $attr='$value'";
            }
        }
        $html .= ">buy</button>";
        return $html;
    }
  
  
    /**
     * @param $scenario
     * @param array $data
     * @return array|bool
     */
    private function get_data($scenario, $products, $type) 
    {
        $data = array();
        $data['data-scenario'] = $scenario;


        $cart_data = $products;
        $data_amount = 0;
        foreach ($cart_data as $item) {
            $data_amount += $item['amount'];
        }
        $data['data-amount'] = $data_amount;
        $data['data-cart'] = json_encode($cart_data);
        $data['data-firstname-required'] = $this->getOption('data-firstname-required');
        $data['data-lastname-required'] = $this->getOption('data-lastname-required');
        $data['data-middlename-required'] = $this->getOption('data-middlename-required');
        $data['data-phone-required'] = $this->getOption('data-phone-required');
        $data['data-email-required'] = $this->getOption('data-email-required');
        $data['data-delivery-required'] = $this->getOption('data-delivery-required');
        $data['data-deliverycases-required'] = $this->getOption('data-deliverycases-required');
        $data['data-pickpoints-required'] = $this->getOption('data-pickpoints-required');
        $data['data-promocode-required'] = $this->getOption('data-promocode-required');
        $data['data-promocard-required'] = $this->getOption('data-promocard-required');
        $data['data-userdata'] = json_encode(array());
        $button_style = $this->get_button_style($type);
        $data['class'] = $button_style['class'];
        $data['style'] = $button_style['style'];

        return $data;
    }
  
  
    /**
     * Получить список стилей кнопки
     * 
     * @param string $type
     * @return array
     */
    private function get_button_style($type)
    {
        $style = array();
        $style['class'][] = 'payqr-button';
        $style['class'][] = $this->getOption($type . 'button_color');
        $style['class'][] = $this->getOption($type . 'button_form');
        $style['class'][] = $this->getOption($type . 'button_gradient');
        $style['class'][] = $this->getOption($type . 'button_text_case');
        $style['class'][] = $this->getOption($type . 'button_text_width');
        $style['class'][] = $this->getOption($type . 'button_text_size');
        $style['class'][] = $this->getOption($type . 'button_shadow');
        $style['style'][] = 'height:' . $this->getOption($type . 'button_height') . ';';
        $style['style'][] = 'width:' . $this->getOption($type . 'button_width') . ';';
        return $style;
    }
    
    /**
     * Получить значение для настроек кнопки
     * 
     * @param string $key
     * @return string
     */
    private function getOption($key)
    {
        $value = "";
        return $value;
    }
}
