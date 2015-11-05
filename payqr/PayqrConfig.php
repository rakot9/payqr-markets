<?php
/**
 * Класс конфигурации
 * Подключите этот файл, чтобы обеспечить автозагрузку всех необходимых классов для работы с API PayQR
 */

if (!defined('PAYQR_ROOT')) {
    define('PAYQR_ROOT', dirname(__FILE__) . '/');
}
require(PAYQR_ROOT . 'library/PayqrAutoload.php');

class PayqrConfig
{
    // по умолчанию ниже продемонстрированы примеры значений, укажите актуальные значения для своего "Магазина"
    public static $merchantID = "094711-13811"; // номер "Магазина" из личного кабинета PayQR
    
    public static $secretKeyIn = "bWIFWbzN3RZG4VXbv38Y"; // входящий ключ из личного кабинета PayQR (SecretKeyIn), используется в уведомлениях от PayQR
    
    public static $secretKeyOut = "ZsQQCXYUveyMDIMA1mXc"; // исходящий ключ из личного кабинета PayQR (SecretKeyOut), используется в запросах в PayQR
    
    public static $logKey = "123"; // Ключ доступа к логам

    public static $logFile =  "payqr.log"; // имя файла логов библиотеки PayQR

    public static $enabledLog = true; // разрешить библиотеке PayQR вести лог

    public static $maxTimeOut = 10; // максимальное время ожидания ответа PayQR на запрос интернет-сайта в PayQR

    public static $checkHeader = true; // проверять секретный ключ SecretKeyIn в уведомлениях и ответах от PayQR

    public static $version_api = '2.0.0'; // версия библиотеки PayQR

    private function  __construct()
    {
    }
}