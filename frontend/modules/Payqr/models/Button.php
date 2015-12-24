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
     * @param \frontend\models\Market $market
     * @return string|void
     */
    public function initBuy(\frontend\models\Market $market = null)
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

    public function initPay(\frontend\models\Market $market = null)
    {
        $html = "";
        if($market && isset($market->settings))
        {
            $settings = json_decode($market->settings, true);
        }
        else
        {
            $settings = array();
        }

        //Производим генерацию скрипта для InSales, чтобы могли реализовывать сценарий pay
        if($RSA = $this->RSAInsalesEncrypt($settings))
        {
            $html .= \yii\bootstrap\Html::beginTag("div", ['class' => 'row form-group']);
            $html .= \yii\bootstrap\Html::beginTag("div", ['class' => 'col-xs-6']);
            $html .= "Кодированная строка:";
            $html .= \yii\bootstrap\Html::endTag("div");

            $html .= \yii\bootstrap\Html::beginTag("div", ['class' => 'col-xs-6']);
            $html .= \yii\bootstrap\Html::beginTag("textarea", ['cols' => 55, 'rows'=>'5']) . "<script>var insales='".$RSA."';</script>" ;
            $html .= \yii\bootstrap\Html::endTag("textarea");
            $html .= \yii\bootstrap\Html::endTag("div");
            $html .= \yii\bootstrap\Html::endTag("div");
        }
        //
        return $html;
    }

    /**
     * @param $xmlRow
     * @param $settings
     * @param bool $place
     * @return string
     */
    private function generateHtml($xmlRow, $settings, $place = false)
    {
        $html = "";
        
        $button_option = $xmlRow['field'];
        
        //Проверка "отображать" или нет элемент
        if(isset($button_option[6]['@attributes']['value']) && $button_option[6]['@attributes']['value'] == "0")
        {
            return $html;
        }
        
        $html .= \yii\bootstrap\Html::beginTag("div", ['class' => 'row form-group']);
            $html .= \yii\bootstrap\Html::beginTag("div", ['class' => 'col-xs-6']);
            $html .= isset($place[1]) ? str_replace("#place#", $place[1], $button_option[4]['@attributes']['value']) : $button_option[4]['@attributes']['value'];
            $html .= \yii\bootstrap\Html::endTag("div");

            $html .= \yii\bootstrap\Html::beginTag("div", ['class' => 'col-xs-6']);

            $fieldName = isset($place[0]) ? $place[0] . $button_option[0]['@attributes']['value'] : $button_option[0]['@attributes']['value'];
            
            $elProperty = array();
            
            //Проверка "разрешить" редактирование или нет
            if($button_option[5]['@attributes']['value'] == "0")
            {
                $elProperty["disabled"] = "disabled";
            }
            //Проверка "отображать" или нет элемент
            if(isset($button_option[6]['@attributes']['value']) && $button_option[6]['@attributes']['value'] == "0")
            {
                $elProperty["style"] = "display:none";
            }
            //Дополнительные атрибуты
            if($button_option[1]['@attributes']['value']=="text")
            {
                $elProperty["size"] = 50;
            }
            
            switch ($button_option[1]['@attributes']['value'])
            {
                case 'text':
                    $match = array();
                    preg_match("/#(.*)#/i", $button_option[2]['@attributes']['value'], $match);
                    $value = (isset($match[1]) && !empty($match[1]))? eval($match[1]) : $button_option[2]['@attributes']['value'];
                    $html .= \yii\bootstrap\Html::textInput($fieldName, 
                                                            isset($settings[$fieldName])? $settings[$fieldName] : $value,  
                                                            /*$button_option[5]['@attributes']['value'] == "0" ? array("disabled" => "disabled", "size" => 50): array("size" => 50)*/
                                                            $elProperty
                            );
                    break;
                case 'select':
                    $select = json_decode($button_option[3]['@attributes']['value'], true);
                    $html .= \yii\bootstrap\Html::dropDownList($fieldName, 
                                                               isset($settings[$fieldName])? $settings[$fieldName] : $button_option[2]['@attributes']['value'], 
                                                               $select, 
                                                               /*$button_option[5]['@attributes']['value'] == "0" ? array("disabled" => "disabled"): array()*/
                                                               $elProperty
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

    /**
     * @param $settings
     * @return string
     */
    private function RSAInsalesEncrypt($settings)
    {
        $publicKey = openssl_pkey_get_public("file://".realpath(dirname(__FILE__).'/../../../web/rsa/pubkey'));

        if(empty($settings["insales_url"]) || empty($settings['secret_key_in']) || empty($settings['secret_key_out']) )
            return "";

        //Получаем информацию из URL
        $insales_url = parse_url($settings["insales_url"]);

        if(isset($insales_url['host'], $insales_url['user'], $insales_url['pass']))
        {
            //user -> индентификатор
            //pass -> пароль
            /**
             * Проверяем хост на наличие порта
             */
            $path = array();
            if(isset($insales_url['path']))
            {
                $insales_url['path'] = trim($insales_url['path'],"/");

                $path = explode("/", $insales_url['path']);

                if(count($path) <= 1 )
                {
                    unset($path);
                }
            }

            openssl_public_encrypt($insales_url['host']. (isset($insales_url['port'])?":".$insales_url['port']:"") . (isset($path, $path[0])?"/".$path[0]:"") . ";" . $insales_url['user'] . ";" . $insales_url['pass'], $encrypted, $publicKey);
        }

        return base64_encode($encrypted);
    }

    public function RSAInsalesDecrypt($string)
    {
        $str = base64_decode($string);

        if($str)
        {
            $privateKey = openssl_pkey_get_private('file://' . realpath(dirname(__FILE__).'/../../../web/rsa/privkey '), "insales");
            openssl_private_decrypt($str, $decrypted, $privateKey);
            return $decrypted;
        }
        return  "";
    }
}