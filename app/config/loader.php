<?php

use Phalcon\Loader;

/**
 * Registering an autoloader
 */
$loader = new Loader();

$loader->registerNamespaces([
    'App\Models'      => $config->application->modelsDir,
    'App\Controllers' => $config->application->controllersDir,
    'App\Common'      => $config->application->commonDir,
    'App'             => $config->application->libraryDir
]);

$loader->register();