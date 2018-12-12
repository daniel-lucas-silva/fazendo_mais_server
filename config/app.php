<?php

use Phalcon\Mvc\Controller;
use Phalcon\Mvc\Micro\Collection as MicroCollection;

/**
 * ACL checks
 */
$app->before(new AccessMiddleware());

/**
 * Auth routes
 */
$auth = new MicroCollection();
$auth->setHandler('AuthController', true);
$auth->setPrefix('/auth');
$auth->post('/login', 'login');
$auth->post('/register', 'register');
$auth->post('/register-login', 'registerAndLogin');
$auth->post('/facebook', 'facebook');
$auth->get('/re-auth', 'reAuth');
$app->mount($auth);

/**
 * Users routes
 */
$users = new MicroCollection();
$users->setHandler('UsersController', true);
$users->setPrefix('/users');
$users->get('/', 'index');
$users->post('/', 'create');
$users->get('/{id}', 'get');
$users->patch('/', 'update');
$app->mount($users);

/**
 * Entity routes
 */
$entities = new MicroCollection();
$entities->setHandler('EntitiesController', true);
$entities->setPrefix('/entities');
$entities->get('/', 'index');
$entities->post('/', 'create');
$entities->get('/{id}', 'get');
$entities->get('/info', 'info');
$entities->patch('/{id}', 'update');
$entities->post('/avatar/{id}', 'avatar');
$app->mount($entities);

/**
 * News routes
 */
$news = new MicroCollection();
$news->setHandler('NewsController', true);
$news->setPrefix('/news');
$news->get('/', 'index');
$news->post('/', 'create');
$news->get('/{id}', 'get');
$news->patch('/{id}', 'update');
$news->delete('/{id}', 'delete');
$app->mount($news);

/**
 * Balance routes
 */
$balance = new MicroCollection();
$balance->setHandler('BalanceController', true);
$balance->setPrefix('/balance');
$balance->get('/', 'index');
$balance->post('/', 'create');
$balance->get('/{id}', 'get');
$balance->patch('/{id}', 'update');
$balance->delete('/{id}', 'delete');
$app->mount($balance);

/**
 * Reports routes
 */
$reports = new MicroCollection();
$reports->setHandler('ReportsController', true);
$reports->setPrefix('/reports');
$reports->get('/', 'index');
$reports->post('/', 'create');
$reports->get('/{id}', 'get');
$reports->patch('/{id}', 'update');
$reports->delete('/{id}', 'delete');
$app->mount($reports);

/**
 * Locations routes
 */
$locations = new MicroCollection();
$locations->setHandler('LocationController', true);
$locations->setPrefix('/location');
$locations->get('/', 'getStates');
$locations->get('/cities', 'getCities');
$locations->get('/{id}', 'getCity');
$app->mount($locations);


$users = new MicroCollection();
$users->setHandler('UsersController', true);
$users->setPrefix('/users');
$users->get('/{id}', 'get');
$app->mount($users);


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
$app->error(
    function ($exception) {
        echo "An error has occurred";
    }
);