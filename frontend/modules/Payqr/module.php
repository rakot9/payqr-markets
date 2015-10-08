<?php

namespace frontend\modules\Payqr;

class module extends \yii\base\Module
{
    public $controllerNamespace = 'frontend\modules\Payqr\controllers';

    public $config;
    
    public function init()
    {
        parent::init();

        //инициализируем кнопку
    }
    
    public function getConfig()
    {
        return $this->config;
    }
}
