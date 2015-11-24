<?php
namespace frontend\modules\Payqr\models;

use Yii;
use yii\base\Model;
use light\yii2\XmlParser;
use yii\helpers\Html;
use frontend\modules\Payqr\models\Market;

class Button extends \yii\base\Model{
    
    /**
     * @var type 
     */
    private static $instance;
    
    private $ShowInPlace = array("cart" => "Корзины", "product" => "Карточки товара", "category" => "Категории товаров");
    
    private $buttonXmlStructure = array();
    
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
    public function init(\frontend\models\Market $market = null)
    {
        if($market && isset($market->settings))
        {
            $settings = json_decode($market->settings, true);
        }
        else
        {
            $settings = array();
        }
        
        $xml_structure = $this->getStructure();
        
        $html = \yii\bootstrap\Html::beginForm('?r=payqr/button/create' . (isset($market->id)? "&market_id=".$market->id : ""), 'post', []);
        
        $html .= \yii\bootstrap\Html::csrfMetaTags();
        
        //инициализируем общие настройки кнопки
        foreach($xml_structure as $row)
        {
            if(isset($row['field'][0]['@attributes']['value']) && !$this->buttonStructure($row))
            {
                $html.= $this->generateHtml($row, $settings);
            }
        }
        //инициализиурем параметры кнопки в соответствии с местом отображения
        foreach($this->ShowInPlace as $place => $placeTranslate)
        {
            foreach($this->buttonXmlStructure as $xmlrow)
            {
                $html.= $this->generateHtml($xmlrow, $settings, array( 0 => $place, 1 => $placeTranslate));
            }
        }
        
        $html .= \yii\bootstrap\Html::submitButton('Сохранить');
        $html .= \yii\bootstrap\Html::endForm();
        
        return $html;
    }
    
    /**
     * 
     * @param type $xmlRow
     * @param type $settings
     * @param type $place - Для какого места (карточка товара, корзина, категория товара) будет настраиваться настройка
     * @return type
     */
    private function generateHtml($xmlRow, $settings, $place = false)
    {
        $html = "";
        
        $button_option = $xmlRow['field'];
            
        $html .= \yii\bootstrap\Html::beginTag("div", ['class' => 'row form-group']);
            $html .= \yii\bootstrap\Html::beginTag("div", ['class' => 'col-xs-6']);
            $html .= isset($place[1]) ? str_replace("#place#", $place[1], $button_option[4]['@attributes']['value']) : $button_option[4]['@attributes']['value'];
            $html .= \yii\bootstrap\Html::endTag("div");

            $html .= \yii\bootstrap\Html::beginTag("div", ['class' => 'col-xs-6']);

            $fieldName = isset($place[0]) ? $place[0] . $button_option[0]['@attributes']['value'] : $button_option[0]['@attributes']['value'];

            switch ($button_option[1]['@attributes']['value'])
            {
                case 'text':
                    $html .= \yii\bootstrap\Html::textInput($fieldName, 
                                                            isset($settings[$fieldName])? $settings[$fieldName] : $button_option[2]['@attributes']['value'],  
                                                            $button_option[5]['@attributes']['value'] == "0" ? array("disabled" => "disabled"): array()
                            );
                    break;
                case 'select':
                    $select = json_decode($button_option[3]['@attributes']['value'], true);
                    $html .= \yii\bootstrap\Html::dropDownList($fieldName, 
                                                               isset($settings[$fieldName])? $settings[$fieldName] : $button_option[2]['@attributes']['value'], 
                                                               $select, 
                                                               $button_option[5]['@attributes']['value'] == "0" ? array("disabled" => "disabled"): array()
                        );
                    break;
            }

            $html .= \yii\bootstrap\Html::endTag("div");
        $html .= \yii\bootstrap\Html::endTag("div");
        
        return $html;
    }
    
    private function buttonStructure($xmlRow)
    {
        $button_option = $xmlRow['field'];
        
        $fieldName = $button_option[0]['@attributes']['value'];
        
        if(strpos($fieldName, "button") !== false)
        {
            $this->buttonXmlStructure[] = $xmlRow;
            
            return true;
        }
        return false;
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
    
    public function prepareStruct2Json(\frontend\models\Market $market = null, $place = "cart")
    {
        // создаем критерий, по которому будем отбирать параметры кнопок
        $filter = array($place, "required");
        
        if(isset($market->settings) && !empty($market->settings))
        {
            $settings = json_decode($market->settings, true);
            
            $buttonSettings = array();
            
            if(is_array($settings))
            {
                $button = array();
                //начинаем преобразовывать кнопку к нужному для нас виду
                //параллельно отсеиваем значения по фильтру из переменной $filter
                
                foreach($settings as $key => $setting)
                {
                    //проверяем соответсвие
                    foreach ($filter as $filtered_value)
                    {
                        if(strpos($key, $filtered_value ) !== false)
                        {
                            if($filtered_value == $place)
                            {
                                $key = str_replace($place, "", $key);

                                if(strpos($key, "button_width")!== false || strpos($key, "button_height")!== false)
                                {
                                    $buttonSettings["style"][] = str_replace("button_", "", $key) . ":" . $setting;
                                }
                                else
                                {
                                    $buttonSettings["class"][] = $key . "-" . $setting;
                                }
                            }
                            else
                            {
                                $buttonSettings["attr"][$key] = $setting;
                            }
                            break;
                        }
                    }
                }
            }
            
            return $buttonSettings;
        }
        
        return array();
    }
}