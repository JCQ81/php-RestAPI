<?php

include('config.php');
include('inc/class_db.php');

// PHP configuration
date_default_timezone_set('UTC');
error_reporting(E_ALL ^ E_NOTICE);

// Ensure $debug state
if (!$debug) { $debug = false; }

// Database connection
$db = new db($db_connectionstring);
$db->debug = $debug;

// Prepare API response
include('inc/class_sAPI.php');
$api = new sAPI(2);
$payload = $api->payload();
$result = null;

/******************************************************************
 * API Routes
 ******************************************************************/

// --> /auth
include('inc/class_sessman.php');
include('inc/api_auth.php');

// --> /test
if ($api->route('/test')) {
  if ($api->method('GET'))      { $result = ['route'=>'/test', 'method'=>'GET' ]; }
  if ($api->method('POST'))     { $result = ['route'=>'/test', 'method'=>'POST' ]; }
}

// --> /test/[id]
if ($api->route('/test/{id}')) {
  if ($api->method('GET'))      { $result = ['route'=>'/test/{id}', 'method'=>'GET', 'id'=>$api->param('id')]; }
  if ($api->method('PUT'))      { $result = ['route'=>'/test/{id}', 'method'=>'PUT', 'id'=>$api->param('id')]; }
  if ($api->method('DELETE'))   { $result = ['route'=>'/test/{id}', 'method'=>'DELETE', 'id'=>$api->param('id')]; }
}

// --> /test/membership/[groupid]
if ($api->route('/test/membership/{groupid}')) {
  if ($api->method('GET'))      { $result = ['ismember'=>$sessman->ismember($api->param('groupid'))]; }
}

// API response / error
if (in_array(gettype($result), ['NULL', 'boolean'])) {
  $api->http_error(404);
}
header('Content-Type: application/json; charset=utf-8');
if ($debug) {
  print(json_encode($result));
}
else {
  header('Content-Encoding: gzip');
  print(gzencode(json_encode($result), 9));
}