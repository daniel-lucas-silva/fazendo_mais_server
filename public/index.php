<?php
use Phalcon\Mvc\Micro;

date_default_timezone_set('America/Sao_Paulo');

define('APPLICATION_ENV', getenv('APPLICATION_ENV') ?: 'production');

if (APPLICATION_ENV === 'development') {

    ini_set('display_errors', "On");
    error_reporting(E_ALL);
    $debug = new Phalcon\Debug();
    $debug->listen();
}

define('APP_PATH', realpath('..'));

try {

    require __DIR__ . '/../vendor/autoload.php';

    $config = include __DIR__ . '/../config/config.php';

    include APP_PATH . '/config/loader.php';
    include APP_PATH . '/config/services.php';
    include APP_PATH . '/config/acl.php';

    $app = new Micro($di);

    include APP_PATH . '/config/app.php';

    $app->handle();

} catch (\Exception $e) {
    if (APPLICATION_ENV === 'development') {
        echo $e->getMessage() . '<br>';
        echo '<pre>' . $e->getTraceAsString() . '</pre>';
    }
}
