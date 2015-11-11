<?php
/**
 * Класс конфигурации
 * Подключите этот файл, чтобы обеспечить автозагрузку всех необходимых классов для работы с API PayQR
 */

use frontend\modules\Payqr\models\Market;

if (!defined('PAYQR_ROOT')) {
    define('PAYQR_ROOT', dirname(__FILE__) . '/');
}

if (!defined('INSALES_DOMEN')) {
    define('INSALES_DOMEN', 'http://payqr.myinsales.ru');
}

if (!defined('DEFAULT_USER_ID')) {
    define('DEFAULT_USER_ID', 1);
}

require(PAYQR_ROOT . 'library/PayqrAutoload.php');

class PayqrConfig
{
    // по умолчанию ниже продемонстрированы примеры значений, укажите актуальные значения для своего "Магазина"
    //public static $merchantID = "094711-13811"; // номер "Магазина" из личного кабинета PayQR
    //public static $secretKeyIn = "bWIFWbzN3RZG4VXbv38Y"; // входящий ключ из личного кабинета PayQR (SecretKeyIn), используется в уведомлениях от PayQR
    //public static $secretKeyOut = "ZsQQCXYUveyMDIMA1mXc"; // исходящий ключ из личного кабинета PayQR (SecretKeyOut), используется в запросах в PayQR
    public static $merchantID;
    public static $secretKeyIn;
    public static $secretKeyOut;
    public static $insalesURL;
    
    public static $logKey = "123"; // Ключ доступа к логам

    public static $logFile =  "payqr.log"; // имя файла логов библиотеки PayQR

    public static $enabledLog = true; // разрешить библиотеке PayQR вести лог

    public static $maxTimeOut = 10; // максимальное время ожидания ответа PayQR на запрос интернет-сайта в PayQR

    public static $checkHeader = true; // проверять секретный ключ SecretKeyIn в уведомлениях и ответах от PayQR

    public static $version_api = '2.0.0'; // версия библиотеки PayQR
    
    // InSales URL's
    public static $urlCreateOrder = "http://f92ee81902e7be44f4f929250580caca:b33ab29af7dac760202ffea745e63cb3@payqr.myinsales.ru/admin/orders";

    private function  __construct(){}
    
    public static function init()
    {
        //получаем информацию о настройках кнопки
        $marketObj = new Market();
        
        $market = $marketObj->getUserMarkets(DEFAULT_USER_ID)->one();
        
        if(!isset($market->settings))
        {
            PayqrLog::log("Не смогли получить настройки кнопки, прекращаем работу!");
            return false;
        }
        
        $settings = json_decode($market->getSettings(), true);
        
        if(!isset($settings['merchant_id'], $settings['secret_key_in'], $settings['secret_key_out'], $settings['insales_url']) &&
           (empty($settings['merchant_id']) || empty($settings['secret_key_in']) || empty($settings['secret_key_out']) || empty($settings['insales_url']) ))
        {
            return false;
        }
        
        self::$merchantID = $settings['merchant_id'];
        
        self::$secretKeyIn = $settings['secret_key_in'];
        
        self::$secretKeyOut = $settings['secret_key_out'];
        
        self::$insalesURL = $settings['insales_url'];
    }
}