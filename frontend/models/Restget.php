<?php

namespace frontend\models;

use Yii;

class Restget extends \yii\db\ActiveRecord{
    
    static public function getResource($typeResource)
    {
        $resource = "";
        
        switch ($typeResource)
        {
            case "button":
                
                $resource = "button";
                
                break;
            
            default:
                
                $resource = "default";
                
                break;
        }
        
        return $resource;
    }
    
}
