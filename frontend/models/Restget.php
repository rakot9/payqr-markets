<?php

namespace frontend\models;

use Yii;

class Restget extends \yii\db\ActiveRecord{
    
    static public function getResource($typeResource, $merchant)
    {
        $resource = "";
        
        switch ($typeResource)
        {
            case "button":
                
                $resource = "button";
                
                //получаем состояние кнопки
                $resource = isset($merchant->settings)? $merchant->settings : "";
                        
                break;
            
            default:
                
                $resource = "default";
                
                break;
        }
        
        return $resource;
    }
    
}
