<?php

use Phalcon\Db\Adapter\Pdo\Mysql;
use Phalcon\Mvc\Url as UrlResolver;
use Phalcon\Mvc\Model\Metadata\Files as MetaDataAdapter;
use Phalcon\Crypt;
use Firebase\JWT\JWT as JWT;

/**
 * Shared configuration service
 */
$di->setShared('config', function () {
    return include APP_PATH . "/config/config.php";
});

$di->setShared('url', function () {
    $config = $this->getConfig();

    $url = new UrlResolver();
    $url->setBaseUri($config->baseUri);
    return $url;
});


$di->setShared('mycrypt', function () {
    $config = $this->getConfig();

    $crypt = new Crypt('aes-256-ctr', true);
    $crypt->setKey($config->get('authentication')->encryption_key);
    return $crypt;
});

$di->setShared('jwt', function () {
    return new JWT();
});

$di->setShared('tokenConfig', function () {
    $config = $this->getConfig();

    $tokenConfig = $config->authentication->toArray();
    return $tokenConfig;
});

$di->setShared('db', function () {
    $config = $this->getConfig();

    $dbConfig = $config->database->toArray();

    $connection = new Mysql($dbConfig);
    $connection->setNestedTransactionsWithSavepoints(true);

    return $connection;
});

$di->setShared('db_log', function () {
    $config = $this->getConfig();

    $dbConfig = $config->log_database->toArray();

    $connection = new Mysql($dbConfig);
    $connection->setNestedTransactionsWithSavepoints(true);

    return $connection;
});

/**
 * If the configuration specify the use of metadata adapter use it or use memory otherwise
 */
$di->set('modelsMetadata', function () {
    $config = $this->getConfig();
    return new MetaDataAdapter([
        'metaDataDir' => $config->application->cacheDir . 'metaData/'
    ]);
});