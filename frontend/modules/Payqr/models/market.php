<?php
namespace frontend\modules\Payqr\models;

use Yii;
use yii\base\Model;
//use frontend\models\Market;

class Market extends \yii\base\Model {
    
    public function getMarkets()
    {
        $market = new Market;
        
        $_m = \frontend\models\Market::findAll(['user_id' => \Yii::$app->getUser()->id]);
        
        return;
    }
    
}
