<?php

use App\BlackBlaze;
use App\Facebook;
use Phalcon\Loader;
use Phalcon\Di\FactoryDefault;
use Phalcon\Config\Adapter\Json;
use Phalcon\Mvc\Url;
use Phalcon\Db\Adapter\Pdo\Mysql;
use Phalcon\Crypt;
use Firebase\JWT\JWT;


define('APPLICATION_ENV', getenv('APPLICATION_ENV') ?: 'production');

if (APPLICATION_ENV === 'development') {
    ini_set('display_errors', "On");
    error_reporting(E_ALL);
    $debug = new Phalcon\Debug();
    $debug->listen();
}

try {


    /**
     * Dependency Injector
     */
    $di = new FactoryDefault();

    $di->setShared('config', function () {
        return new Json( __DIR__ . "/../config.json");
    });

    /**
     * The URL component is used to generate all kind of urls in the application
     */
    $di->setShared('url', function () {
        $config = $this->getConfig();

        $url = new Url();
        $url->setBaseUri($config->baseUri);
        return $url;
    });

    /**
     * Crypt service
     */
    $di->setShared('crypt', function () {
        $config = $this->getConfig();

        $crypt = new Crypt('aes-256-ctr', true);
        $crypt->setKey($config->authentication->encryption_key);
        return $crypt;
    });

    /**
     * JWT service
     */
    $di->setShared('jwt', function () {
        return new JWT();
    });

    /**
     * Facebook service
     */
    $di->setShared('facebook', function () {
        return new Facebook();
    });

    /**
     * BlackBlaze service
     */
    $di->setShared('blackBlaze', function () {
        return new BlackBlaze();
    });

    /**
     * tokenConfig
     */
    $di->setShared('tokenConfig', function () {
        $config = $this->getConfig();

        $tokenConfig = $config->authentication->toArray();
        return $tokenConfig;
    });

    /**
     * Database connection is created based in the parameters defined in the configuration file
     */
    $di->setShared('db', function () {
        $config = $this->getConfig();

        $dbConfig = $config->database->toArray();

        $connection = new Mysql($dbConfig);
        $connection->setNestedTransactionsWithSavepoints(true);

        return $connection;
    });

    /**
     * Registering an autoloader
     */
    $loader = new Loader();

    $loader->registerNamespaces([
        'App\Models'      => __DIR__ . "/models/",
        'App\Controllers' => __DIR__ . "/controllers/",
        'App\Common'      => __DIR__ . "/common/",
        'App'             => __DIR__ . "/library/",
    ]);

    $loader->register();

    require_once __DIR__ . "/routes.php";

} catch (Exception $e) {
    if (APPLICATION_ENV === 'development') {
        echo $e->getMessage() . '<br>';
        echo '<pre>' . $e->getTraceAsString() . '</pre>';
    }
}