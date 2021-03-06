<?php

use App\Acl;
use Phalcon\Mvc\Micro\Collection;
use Phalcon\Mvc\Micro;

/*
 * Starting the application
 * Assign service locator to the application
 */
$app = new Micro($di);

/**
 * ACL checks
 * @noinspection PhpParamsInspection
 */
$app->before(new Acl());

/**
 * Index routes
 */
$index = new Collection();
$index->setHandler('App\Controller\IndexController', true);
$index->get('/', 'index');
$app->mount($index);

/**
 * User routes
 */
$users = new Collection();
$users->setHandler('App\Controller\UsersController', true);
$users->setPrefix('/users');
$users->get('/', 'fetch');
$users->post('/', 'create');
$users->get('/{id}', 'get');
$users->patch('/{id}', 'update');
$users->delete('/{id}', 'delete');
$users->post('/login', 'login');
$users->patch('/change-password/{id}', 'changePassword');
$app->mount($users);

/**
 * Not Found handler
 */
$app->notFound(function () use ($app) {
    $app->response->setStatusCode(404, "Not Found")->sendHeaders();
    $app->response->setContentType('application/json', 'UTF-8');
    $app->response->setJsonContent(array(
        "status" => "error",
        "code" => "404",
        "messages" => "URL Not found",
    ));
    $app->response->send();
});

/**
 * Error handler
 */
$app->error(function ($exception) use ($app) {
    if(APPLICATION_ENV != 'development') {
        $app->response->setStatusCode(500, "Internal Server Error")->sendHeaders();
        $app->response->setContentType('application/json', 'UTF-8');
        $app->response->setJsonContent(array(
            "status" => "error",
            "code" => "500",
            "messages" => "Internal Server Error"
        ));
        $app->response->send();
        exit;
    }
    return $exception;
});

$app->handle();