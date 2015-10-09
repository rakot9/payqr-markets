<?php

namespace frontend\modules\Payqr\controllers;

use yii\web\Controller;
use frontend\modules\Payqr\models\Button;
use frontend\models\Market;

class ButtonController extends Controller
{
    public function actionIndex()
    {
        return $this->render('index',[
        ]);
    }
    
    /**
     * Редактирование свойств кнопки
     * @param type $market_id
     * @return type
     */
    public function actionEdit($market_id = 0)
    {
        $button = Button::getInstance()->init(Market::findOne($market_id));
        
        return $this->render('edit', [
            'button' => $button
        ]);
    }

    /**
     * Метод обновляет/создает параметр кнопки
     * @param type $market_id
     */
    public function actionCreate($market_id = 0)
    {
        if(!empty($market_id))
        {
            $market = Market::findOne($market_id);
            
            if($market)
            {
                $market->settings = json_encode(\Yii::$app->request->post());
                $market->save();
            }
        }
        else
        {
            $market = new Market;
            $market->user_id = \Yii::$app->getUser()->id;
            $market->name = "Тестовый магазин";
            $market->settings = json_encode(\Yii::$app->request->post());
            $market->save();
        }
        
        $this->redirect('?r=payqr/button/edit&market_id=' . $market_id);
    }
}
