<?php

return [
  'baseUri' => '//fazendo.mais',
  'database' => [
    'adapter' => 'Mysql', /* Possible Values: Mysql, Postgres, Sqlite */
    'host' => '127.0.0.1',
    'username' => 'root',
    'password' => 'root',
    'dbname' => 'fazendo.mais',
    'charset' => 'utf8',
  ],
  'log_database' => [
    'adapter' => 'Mysql', /* Possible Values: Mysql, Postgres, Sqlite */
    'host' => '127.0.0.1',
    'username' => 'root',
    'password' => 'root',
    'dbname' => 'fazendo.mais.log',
    'charset' => 'utf8',
  ],
  'authentication' => [
    'secret' => 'i_see_dead_people', // This will sign the token. (still insecure)
    'encryption_key' => '(jqvvQ"#s%]XI42k=K~_VV861`p|8/', // Secure token with an ultra password
    'expiration_time' => 86400 * 7, // One week till token expires
    'iss' => " api.fazendo.mais", // Token issuer eg. api.fazendo.mais
    'aud' => " api.fazendo.mais", // Token audience eg. api.fazendo.mais
  ],
];
