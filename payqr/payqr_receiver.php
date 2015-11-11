<?php
/**
 * Скрипт принимает и обрабатывает уведомления от PayQR
 */
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');
require(__DIR__ . '/../common/config/bootstrap.php');
require(__DIR__ . '/../console/config/bootstrap.php');

$config = yii\helpers\ArrayHelper::merge(
    require(__DIR__ . '/../common/config/main.php'),
    require(__DIR__ . '/../common/config/main-local.php'),
    require(__DIR__ . '/../console/config/main.php'),
    require(__DIR__ . '/../console/config/main-local.php')
);

$application = new yii\console\Application($config);
$exitCode = $application->run();

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

exit($exitCode);