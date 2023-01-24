<?php

$debug = false;

$db_connectionstring = 'pgsql:host=127.0.0.1;dbname=php-RestAPI;user=MyAPI;password=MyAPI';

$sessman_config = [

  'timeout'  => 600,

  'ldap'    => [
    'enabled'       => false,
    'host'          => '127.0.0.1',
    'port'          => 389,
    'userdn'        => 'cn=users,cn=accounts,dc=example,dc=local',
    'group-auth'    => 'cn=restapi-auth,cn=groups,cn=accounts,dc=example,dc=local',
    'group-sa'      => 'cn=restapi-sa,cn=groups,cn=accounts,dc=example,dc=local'
  ], 

  'radius'  => [
    'enabled'       => false,
    'host'          => '127.0.0.1',
    'port'          => 1812,
    'secret'        => 'testing123',
    'attr'          => 230,
    'group-auth'    => 'restapi-auth',
    'group-sa'      => 'restapi-sa'
  ],

];