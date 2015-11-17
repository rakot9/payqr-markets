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
    
    public function actionDelete($market_id)
    {
        Market::findOne($market_id)->delete();
        
        $this->redirect('/');
    }

    /**
     * Метод обновляет/создает параметр кнопки
     * @param type $market_id
     */
    public function actionCreate($market_id = 0)
    {
        $isMerchant = $this->issetMerchantId(null, \Yii::$app->request->post());
            
        if(empty($market_id) && (is_null($isMerchant) || (is_bool($isMerchant) && $isMerchant)))
        {
            return false;
        }
        
        //exit($isMerchant);
        
        if(!empty($market_id))
        {
            $market = Market::findOne($market_id);
            
            if($market)
            {
                if(is_string($isMerchant)) {
                    $market->name = $isMerchant;
                }
                $market->settings = json_encode(\Yii::$app->request->post());
                $market->save();
            }
        }
        else
        {
            $market = new Market;
            $market->user_id = \Yii::$app->getUser()->id;
            $market->name = is_string($isMerchant)? $isMerchant : "Ваш магазин";
            $market->settings = json_encode(\Yii::$app->request->post());
            $market->save();
            $market_id = $market->id;
        }
        
        $this->redirect(empty($market_id)? '/' :  '?r=payqr/button/edit&market_id=' . $market_id);
    }
    
    /**
     * 
     * @param type $merchantId
     * @param type $settings
     * @return boolean|string
     */
    private function issetMerchantId($merchantId = null, $settings = array())
    {
        $_merchantId = null;
                
        if(empty($merchantId) && empty($settings))
            return null;
        
        if(!empty($merchantId))
        {
            $_merchantId = $merchantId;
        }
        
        if(!empty($settings) && is_array($settings) && empty($_merchantId))
        {
            if(isset($settings['merchant_id']) && empty($settings['merchant_id']))
                return null;
            
            $_merchantId = $settings['merchant_id'];
        }
        else
        {
            return null;
        }
        
        
        $result = \frontend\models\Market::find()->select(['id','settings'])->where("settings LIKE '%" . $_merchantId . "%'")->one();
        
        if($result && isset($result->id))
        {
            return true;
        }
        
        return $_merchantId;
    }
}
