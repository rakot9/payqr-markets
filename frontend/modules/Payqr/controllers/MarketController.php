<?php

namespace frontend\modules\Payqr\controllers;

use yii\web\Controller;
use frontend\modules\Payqr\models\Market;

class MarketController extends Controller
{
    public function actionIndex()
    {
        $markets = new Market();
        
        return $this->render('index', [
            'dataProvider' => new \yii\data\ActiveDataProvider([
                'query' => $markets->getMarkets(),
                'pagination' => [
                    'pageSize' => 10,
                ]
            ])
        ]);
    }
}
