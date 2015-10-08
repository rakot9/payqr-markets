<?php

namespace frontend\modules\Payqr\controllers;

use yii\web\Controller;

class MarketController extends Controller
{
    public function actionIndex()
    {
        return $this->render('index');
    }
}
