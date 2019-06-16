<?php

use Phalcon\Acl;
use Phalcon\Acl\Adapter\Memory;
use Phalcon\Acl\Resource;
use Phalcon\Acl\Role;

$acl = new Memory();

$acl->setDefaultAction(Acl::DENY);

$acl->addRole(new Role('Guest'));
$acl->addRole(new Role('Donor'));
$acl->addRole(new Role('Entity'));
$acl->addRole(new Role('Admin'));

$acl->addInherit('Admin', 'Entity');

$arrResources = [
    'Guest' => [
        'Users'  => ['login', 'create', 'profile'],
    ],
    'Donor' => [
        'Users' => ['me', 'update', 'changePassword'],
    ],
    'Entity' => [
        'Users'  => ['me', 'update', 'changePassword'],
    ],
    'Admin' => [
        'Users' => ['delete'],
    ],
];

foreach ($arrResources as $arrResource) {
    foreach ($arrResource as $controller => $arrMethods) {
        $acl->addResource(new Resource($controller), $arrMethods);
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
