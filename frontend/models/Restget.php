<?php

namespace frontend\models;

use Yii;
use frontend\modules\Payqr\models\Button;

class Restget extends \yii\db\ActiveRecord{
    
    /**
     * 
     * @param type $typeResource
     * @param \frontend\models\Market $market
     * @param type $param
     * @return string
     */
    static public function getResource($typeResource, Market $market, $param = array())
    {
        $resource = "";
        
        switch ($typeResource)
        {
            case "button":
                
                $resource = "button";
                
                //получаем состояние кнопки
                $resource = isset($market->settings)? $market->settings : "";
                
                //преобразуем данные кнопки
                $resource = Button::getInstance()->prepareStruct2Json($market, isset($param[$typeResource]["place"])? $param[$typeResource]["place"] : "cart" );
                        
                break;
            
            default:
                
                $resource = "default";
                
                break;
        }
        
        return $resource;
    }

    /**
     * Метод проверяет, отображать ли кнопку на странице
     * @var \frontend\models\Market $market
     * @var string $place
     * @return bool
     */
    static public function isShowButton(Market $market, $place = "")
    {
        if(empty($place) || !isset($market->settings))
        {
            return false;
        }

        $buttonSettings = json_decode($market->settings, true);

        if($buttonSettings && isset($buttonSettings[ $place . "payqr-button_showinplace"]) && "yes" == $buttonSettings[ $place . "payqr-button_showinplace"])
        {
            return true;
        }

        return false;
    }
    
}
