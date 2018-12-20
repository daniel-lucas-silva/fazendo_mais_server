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
$auth->post('/facebook', 'facebook');
$auth->patch('/change-password', 'changePassword');
$auth->get('/me', 'me');
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
$entities->patch('/{id}', 'update');
$entities->delete('/{id}', 'delete');
$entities->get('/search/{text}', 'search');
$app->mount($entities);

/**
 * News routes
 */
$news = new MicroCollection();
$news->setHandler('NewsController', true);
$news->setPrefix('/news');
$news->get('/{entity_id}', 'index');
$news->get('/{entity_id}/search/{text}', 'search');
$news->get('/get/{id}', 'get');
$news->post('/', 'create');
$news->patch('/{id}', 'update');
$news->delete('/{id}', 'delete');
$app->mount($news);

/**
 * Categories routes
 */
$categories = new MicroCollection();
$categories->setHandler('CategoriesController', true);
$categories->setPrefix('/categories');
$categories->get('/', 'index');
$categories->get('/search/{text}', 'search');
$categories->post('/', 'create');
$categories->get('/{id}', 'get');
$categories->patch('/{id}', 'update');
$categories->delete('/{id}', 'delete');
$app->mount($categories);

/**
 * Balance routes
 */
$balance = new MicroCollection();
$balance->setHandler('BalanceController', true);
$balance->setPrefix('/balance');
$balance->get('/{entity_id}', 'index');
$balance->get('/{entity_id}/search/{text}', 'search');
$balance->get('/get/{id}', 'get');
$balance->post('/', 'create');
$balance->patch('/{id}', 'update');
$balance->delete('/{id}', 'delete');
$app->mount($balance);

/**
 * Reports routes
 */
$reports = new MicroCollection();
$reports->setHandler('ReportsController', true);
$reports->setPrefix('/reports');
$reports->get('/{entity_id}', 'index');
$reports->get('/{entity_id}/search/{text}', 'search');
$reports->get('/get/{id}', 'get');
$reports->post('/', 'create');
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