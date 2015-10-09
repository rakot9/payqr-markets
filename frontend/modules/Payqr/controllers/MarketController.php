<?php

namespace frontend\modules\Payqr\controllers;

use yii\web\Controller;
use frontend\modules\Payqr\models\Market;

class MarketController extends Controller
{
    public function actionIndex()
    {
        $markets = new Market();
        
        $markets->getMarkets();
        
        return $this->render('index');
    }
}
