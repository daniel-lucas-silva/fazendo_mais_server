<?php

$acl = new Phalcon\Acl\Adapter\Memory();

$acl->setDefaultAction(Phalcon\Acl::DENY);

$acl->addRole(new Phalcon\Acl\Role('Guest'));
$acl->addRole(new Phalcon\Acl\Role('Donor'));
$acl->addRole(new Phalcon\Acl\Role('Entity'));
$acl->addRole(new Phalcon\Acl\Role('Admin'));

$acl->addInherit('Admin', 'Entity');

$arrResources = [

  'Guest' => [
    'Auth'          => ['login', 'register', 'facebook', 'changePassword'],
    'Location'      => ['states', 'cities', 'get'],
    'Entities'      => ['get', 'index', 'search'],
    'Categories'    => ['get', 'index', 'search'],
    'Balance'       => ['index', 'get', 'search'],
    'News'          => ['index', 'get', 'search'],
    'Reports'       => ['index', 'get', 'search'],
    'Upload'        => ['image', 'document']
  ],
  'Donor' => [
    'Auth'    => ['me'],
    'Users'         => ['update'],
  ],
  'Entity' => [
    'Auth'    => ['me'],
    'Entities'      => ['create', 'update', 'info', 'avatar'],
    'Balance'       => ['create', 'update', 'delete' ],
    'News'          => ['create', 'update', 'delete' ],
    'Reports'       => ['create', 'update', 'delete' ],
    'Users'         => ['get'],
  ],
  'Admin' => [
    'Auth'    => ['me'],
    'Entities' => ['delete'],
    'Categories' => ['create', 'update', 'delete'],
    'Users'    => ['index', 'search', 'create', 'delete'],
  ],
];

foreach ($arrResources as $arrResource) {
  foreach ($arrResource as $controller => $arrMethods) {
    $acl->addResource(new Phalcon\Acl\Resource($controller), $arrMethods);
  }
}

foreach ($acl->getRoles() as $objRole) {
  $roleName = $objRole->getName();

  foreach ($arrResources['Guest'] as $resource => $method) {
    $acl->allow($roleName, $resource, $method);
  }

  if ($roleName == 'Donor') {
    foreach ($arrResources['Donor'] as $resource => $method) {
      $acl->allow($roleName, $resource, $method);
    }
  }

  if ($roleName == 'Entity') {
    foreach ($arrResources['Entity'] as $resource => $method) {
      $acl->allow($roleName, $resource, $method);
    }
  }

  if ($roleName == 'Admin') {
    foreach ($arrResources['Admin'] as $resource => $method) {
      $acl->allow($roleName, $resource, $method);
    }
  }
}
