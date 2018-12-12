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
    'Auth'          => ['login', 'register', 'registerAndLogin', 'facebook', 'reAuth'],
    'Users'         => ['changePassword'],
    'Location'      => ['states', 'cities', 'get'],
    'Entities'      => ['get', 'index'],
    'Balance'       => ['index', 'get'],
    'News'          => ['index', 'get'],
    'Reports'       => ['index', 'get'],
    'Upload'          => ['image', 'document']
  ],
  'Donor' => [
    'Users'         => ['update'],
  ],
  'Entity' => [
    'Entities'      => ['create', 'update', 'info', 'avatar'],
    'Balance'       => ['create', 'update', 'delete' ],
    'News'          => ['create', 'update', 'delete' ],
    'Reports'       => ['create', 'update', 'delete' ],
    'Users'         => ['get'],
  ],
  'Admin' => [
    'Entities' => ['delete'],
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
