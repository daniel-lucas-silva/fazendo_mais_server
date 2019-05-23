<?php

use Phalcon\Di\FactoryDefault;
use Phalcon\Mvc\Micro;

define('APPLICATION_ENV', getenv('APPLICATION_ENV') ?: 'production');
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');

if (APPLICATION_ENV === 'development') {
    ini_set('display_errors', "On");
    error_reporting(E_ALL);
    $debug = new Phalcon\Debug();
    $debug->listen();
}

try {

    require __DIR__ . '/../vendor/autoload.php';

    /**
     * The FactoryDefault Dependency Injector automatically registers the services that
     * provide a full stack framework. These default services can be overidden with custom ones.
     */
    $di = new FactoryDefault();

    include APP_PATH . '/config/services.php';

    /**
     * Get config service for use in inline setup below
     */
    $config = $di->getConfig();

    include APP_PATH . '/config/loader.php';

    include APP_PATH . '/config/acl.php';

    $app = new Micro($di);

    include APP_PATH . '/app.php';

    $app->handle();

} catch (\Exception $e) {
    if (APPLICATION_ENV === 'development') {
        echo $e->getMessage() . '<br>';
        echo '<pre>' . $e->getTraceAsString() . '</pre>';
    }
}
