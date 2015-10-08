<?php
namespace frontend\modules\Payqr\models;

use Yii;
use yii\base\Model;

class Button extends \yii\base\Model{
    
    /**
     * @var type 
     */
    private static $instance;

    /**
     * В классе реализован singletone, конструктор не используем
     */
    public function __construct()
    {
    }
    
    public static function getInstance()
    {
        if(isset(self::$instance) && (self::$instance instanceof Button))
        {
            return self::$instance;
        }
        else
        {
            return new self();
        }
    }
    
    /**
     * Инициализация кнопки
     */
    public function init()
    {
        return $this->getStructure();
    }
    
    /**
     * Получение структуры кнопки
     */
    private function getStructure()
    {
        if(isset(Yii::$app->getModule('payqr')->config['xml']) && 
                is_file(Yii::$app->getModule('payqr')->config['xml']))
        {
            
            return Yii::$app->getModule('payqr')->config;
        }
    }
}