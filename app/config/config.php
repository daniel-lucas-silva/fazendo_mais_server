<?php

use Phalcon\Config;

$config = new Config([
    'application' => [
        'title' => 'Fazendo Mais',
        'description' => 'API REST',
        'controllersDir' => APP_PATH . '/controllers/',
        'modelsDir'      => APP_PATH . '/models/',
        'libraryDir'     => APP_PATH . '/library/',
        'commonDir'      => APP_PATH . '/common/',
        'cacheDir'       => BASE_PATH . '/cache/',
    ],
]);

$configOverride = new Config(include_once __DIR__ . "/../config/server." . APPLICATION_ENV . ".php");

$config = $config->merge($configOverride);

return $config;
