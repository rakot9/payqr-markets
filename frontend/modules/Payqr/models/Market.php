<?php
namespace frontend\modules\Payqr\models;

use Yii;
use yii\base\Model;

class Market extends \yii\base\Model {
    
    public function getUserMarkets($user_id = null)
    {
        return \frontend\models\Market::find(['user_id' => $user_id? $user_id : \Yii::$app->getUser()->id]);
    }
    
    public function getMarket($merchant_id = null)
    {
        return $merchant_id? \frontend\models\Market::find()->select(['id','settings'])->where("settings LIKE '%" . $merchant_id . "%'")->one() : $merchant_id;
    }
}