<?php
namespace frontend\modules\Payqr\models;

use Yii;
use yii\base\Model;

class Market extends \yii\base\Model {
    
    public function getMarkets()
    {
        return \frontend\models\Market::find(['user_id' => \Yii::$app->getUser()->id]);
    }
    
}