<?php
namespace frontend\modules\Payqr\models;

use Yii;
use yii\base\Model;
use light\yii2\XmlParser;
use yii\helpers\Html;

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
        $xml_structure = $this->getStructure();
        
        $html = \yii\bootstrap\Html::beginForm('?r=payqr/button/index', 'post', []);
        
        $html .= \yii\bootstrap\Html::csrfMetaTags();
        
        foreach($xml_structure as $row)
        {
            $button_option = $row['field'];
            
            $html .= \yii\bootstrap\Html::beginTag("div", ['class' => 'row form-group']);
                $html .= \yii\bootstrap\Html::beginTag("div", ['class' => 'col-xs-6']);
                $html .= $button_option[4]['@attributes']['value'];
                $html .= \yii\bootstrap\Html::endTag("div");
                
                $html .= \yii\bootstrap\Html::beginTag("div", ['class' => 'col-xs-6']);
                
                switch ($button_option[1]['@attributes']['value'])
                {
                    case 'text':
                        $html .= \yii\bootstrap\Html::textInput($button_option[0]['@attributes']['value'], $button_option[2]['@attributes']['value']);
                        break;
                    case 'select':
                        $select = json_decode($button_option[3]['@attributes']['value'], true);
                        $html .= \yii\bootstrap\Html::dropDownList($button_option[0]['@attributes']['value'], 1, $select);
                        break;
                }
                
                $html .= \yii\bootstrap\Html::endTag("div");
            $html .= \yii\bootstrap\Html::endTag("div");
        }
        $html .= \yii\bootstrap\Html::submitButton('Создать кнопку');
        $html .= \yii\bootstrap\Html::endForm();
        return $html;
    }
    
    /**
     * Получение структуры кнопки
     * @return type
     */
    private function getStructure()
    {
        if(isset(Yii::$app->getModule('payqr')->config['xml']) && 
                is_file(Yii::$app->getModule('payqr')->config['xml']))
        {
            $parser = new XmlParser;
            
            $xmlObject = $parser->parse(file_get_contents(Yii::$app->getModule('payqr')->config['xml']), ''); 
            
            return isset($xmlObject['object']) ? $xmlObject['object'] : array();
        }
        return array();
    }
}