<?php
/**
 * Скрипт принимает и обрабатывает уведомления от PayQR
 */

require_once __DIR__ . "/PayqrConfig.php"; // подключаем основной класс
try
{
    $receiver = new PayqrReceiver();
    $receiver->handle();
}
catch (PayqrExeption $e)
{
    echo $e->response;
}

