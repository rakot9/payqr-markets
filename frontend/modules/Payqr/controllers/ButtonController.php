<?php

namespace frontend\modules\Payqr\controllers;

use yii\web\Controller;
use frontend\modules\Payqr\models\Button;

class ButtonController extends Controller
{
    public function actionIndex()
    {
        $button = Button::getInstance()->init();
        
        return $this->render('index',[
            'button' => $button
        ]);
    }
}
