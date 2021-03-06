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
     * @param int $market_id
     * @return string
     */
    public function actionEdit($market_id = 0)
    {
        $market = Market::findOne($market_id);
        /**
         * scenarion
         * $pay
         * $buy
         */
        $buy = Button::getInstance()->initBuy($market);
        $pay = Button::getInstance()->initPay($market);

        echo Button::getInstance()->RSAInsalesDecrypt('mcEwHxdtxcGpW+1rHxg3u5dr10kIS0OIGaVK268YzHLda+fbOGxxPX4NuYyUzjlOW6DjJnkeE0G59jY4KZ+11pd9R3QpPL8usVnzSkxcIC+bUC8olt5uyvEXipoNFBarkqwN4rDBIg6I2YPwQX9JoRMkUUfzUv15LXpRc+I/gUIygEn8+z71Jo14xMSgm/xPPAomRtT68OfE8VHkHv0H5fYnFsSHtLqeBU5iha63B5AYj5SCthP1RWXP0zaMrDYIe9K0xuP3uDlK5OH/+9rIuSm9/d+encEpX5Ds7obV6Vd0I5VVaO2sGbhhHX+IOCrri9MyaoVzLscyVcJpIEPapQ==');

        return $this->render('edit', [
            'buy' => $buy,
            'pay' => $pay,
        ]);
    }
    
    public function actionDelete($market_id)
    {
        Market::findOne($market_id)->delete();
        
        $this->redirect('/');
    }

    /**
     * Метод обновляет/создает параметр кнопки
     * @param int $market_id
     * @return bool
     */
    public function actionCreate($market_id = 0)
    {
        $isMerchant = $this->issetMerchantId(null, \Yii::$app->request->post());
            
        if(empty($market_id) && (is_null($isMerchant) || (is_bool($isMerchant) && $isMerchant)))
        {
            return false;
        }
        
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
     * @param null $merchantId
     * @param array $settings
     * @return bool|null
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
