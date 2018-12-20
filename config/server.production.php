<?php

return [
  'baseUri' => '//my.domain.com/',
  'database' => [
    'adapter' => 'Mysql',
    'host' => 'localhost',
    'username' => '',
    'password' => '',
    'dbname' => '',
    'charset' => 'utf8',
  ],
  'log_database' => [
    'adapter' => 'Mysql',
    'host' => 'localhost',
    'username' => '',
    'password' => '',
    'dbname' => '',
    'charset' => 'utf8',
  ],
  'authentication' => [
    'secret' => '',
    'encryption_key' => '',
    'expiration_time' => 86400 * 31,
    'iss' => "",
    'aud' => "",
  ],
];
