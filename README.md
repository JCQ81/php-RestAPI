
# PHP sAPI (simpleAPI)

A simple PHP RESTful API class, including an authentication class with database/LDAP/Radius support.

For using authentication the source includes a full setup which does not require any adjustments for full functionality. Just include _api_auth.php_ and you are ready to go.

This README contains some basic examples. The repository also contains a working base setup, which enables you to just download the full code, setup _config.php_ and start building from there.

For two factor authentication, please implement an LDAP or Radius solution that supports two factor (like freeIPA with freeOTP or hardware tokens)

## Functions

* [sAPI](#sapi)
* [Authentication](#authentication)
* [Authentication on LDAP/Radius](#authentication-on-ldapradius)
* [Authentication API Reference](#authentication-api-reference)

## sAPI

| Function | Description |
|---|---|
| route($path) | Returns true on match, supports parameters: {param} |
| method($method) | Returns true on HTTP method match |
| param($parameter) | Returns parameter from route |
| payload() | Returns HTTP payload |
| http_error($code) | Dies with HTTP error code |

### Basic example

cURL:

```javascript
curl -X POST -d '{"test":"test"}' https://myserver.fqdn/api.php/v2/path/to/example/000001
```

api.php:

```php
<?php
// Init:
include('inc/class_sAPI.php');
$result = null;
$version = 2;
$api = new sAPI($version);

// Route:
if ($api->route('/path/to/{type}/{id}')) {
  if ($api->method('POST')) {
    $result = [
      'type'    =>  $api->param('type'),
      'id'      =>  $api->param('id'),
      'payload' =>  $api->payload();
    ];    
  }
}

// Error:
if ($result == null) {
  $api->http_error(404);
}

// Response:
header('Content-Type: application/json; charset=utf-8');
header('Content-Encoding: gzip');
print(gzencode(json_encode($result),9));
```

Response:

```javascript
{
  "type": "example",
  "id": "000001",
  "payload": {
    "test": "test"
  }
}
```

## Authentication

To implement authentication setup your database, and include the database and authentication _.php_ files between the sAPI instance declaration and your custom routes.

api.php:

```php
<?php
// Init:
include('inc/class_sAPI.php');
$result = null;
$version = 2;
$api = new sAPI($version);

// Database
include('config.php');
include('inc/class_db.php');
$db = new db("pgsql:host=127.0.0.1;dbname=mydbname;user=mydbuser;password=mydbpassword");

// Route: /auth
include('inc/class_sessman.php');
include('inc/api_auth.php');

// Route:
if ($api->route('/path/to/{type}/{id}')) {
  if ($api->method('POST')) {
    $result = [
      'type'    =>  $api->param('type'),
      'id'      =>  $api->param('id'),
      'payload' =>  $api->payload();
    ];    
  }
}

// Error:
if ($result == null) {
  $api->http_error(404);
}

// Response:
header('Content-Type: application/json; charset=utf-8');
header('Content-Encoding: gzip');
print(gzencode(json_encode($result),9));
```

## Authentication on LDAP/Radius

For implementing LDAP and/or Radius authentication please see the full example fileset as available in this repository.

When implementing LDAP and/or Radius authentication please note:

* Authentication will commence in the following order:
  1. LDAP
  2. Radius
  3. Database
* Radius support requires packaga _php-pecl-radius_
* When authentication fails on a step it will try authenticating with the same parameters on the next step. Authentication will fail if all methods have failed.
* Access is allowed when the LDAP/Radius user is member of the configured group or atttribute as configured in _group-auth_.
* Likewise, SuperAdmin permissions require membership of the group configured in _group-sa_.
* User tokens are primarily designed for usage by dedicated API users. Tokens for LDAP/Radius users is not yet supported.
* Groups configured in the API can have an _ldapcn_ or _radiusattr_ value configured. Users authenticated by LDAP or Radius will automatically have the group's permissions when exactly matching these configurations.
  * LDAP: Matching the full DN, e.g.: _cn=mygroup-auth,cn=groups,cn=accounts,dc=example,dc=local_
  * Radius: Matching name in the attribute's string (comma separated)
* Development
  * LDAP support has been developed and tested _only_ with freeIPA
  * Radius support has been developed and tested _only_ with freeradius

### Radius attribute configuration

While Radius is not meant for this kind of application it may still be nice to have. Since there is no such thing as groups in Radius, matching groups is still supported by adding a comma separated list of group names/tags as a Radius attribute. A small description:

#### Config

Mase sure the configured attribute id matches the entry in your freeradius dictonary

config.php

```php
$config_radius = [
  ...
  'attr'          => 230,
  'group-auth'    => 'auth',
  'group-sa'      => 'sa'
],
```

#### freeradius

_/etc/raddb/dictionary_

```javascript
ATTRIBUTE    MyAppGroups    230    string
```

_/etc/raddb/sites-enabled/default_ > _post-auth_

```javascript
update reply {
  MyAppGroup = "auth, sa, Custom Group 1"
}
```

## Authentication API Reference

#### Token

| HTTP Request Header |
|---|
| _X-API-Key: [token]_ |
  
```javascript
curl -X POST -H 'X-API-Key: nLO0v3OxbsRVyq6w58CyU8NZp1' https://myserver.fqdn/php.api/v2/path/to/example/000001
```

#### /auth

| Method | Description |
|---|---|
| GET | Returns authorization state |
| POST | Login with username and password |
| PUT | Update authorization / keepalive |
| DELETE | Logout |

GET:  

```javascript
// Response
{ "active": true }
```

POST:  

```javascript
// Payload
{ "username": "myusername", "password": "myPa%%w0rD" }
// Response
{ "active": true }
```

PUT:  

```javascript
// Response
{ "active": true }
```

DELETE:

```javascript
// Response
{ "active": false }
```

#### /auth/user

_Only allowed by SuperAdmin_

| Method | Description |
|---|---|
| GET | List users |
| POST | Create new user |

GET:  

```javascript
// Response
[
  {
    "id": "8de44456-a869-46bb-8387-17ac2e1cda38",
    "username": "admin",
    "firstname": null,
    "lastname": null,
    "tokens": false,
    "sa": true
  },
  {..}
]
```

POST:  

```javascript
// Payload
{    
  "username": "myusername",
  "password": "myPa%%w0rD",
  "firstname": "John",
  "lastname": "Doe",
  "tokens": true,
  "sa": false
}
// Response
{ "id": "b01ac1e5-ce38-4189-99ac-b965013d9e24" }
```

#### /auth/user/{user}

Since the user id remains hidden for the user itself, actions for the active user can be accessed by requesting **self** instead of the id (so: _**/auth/user/self**_). This can be used to e.g. gather the display name using GET, or resetting the password using POST.
  
_/auth/user/self is allowed for all users_

_Others only allowed by SuperAdmin_

| Method | Description |
|---|---|
| GET | Get user info |
| PUT | Update user info |
| DELETE | Delete user |

GET:  

```javascript
// Response
{
  "username": "admin",
  "firstname": null,
  "lastname": null,
  "tokens": false,
  "sa": true
}
```

PUT:  

```javascript
// Payload
{    
  "username": "myusername",
  "password": "myPa%%w0rD",
  "firstname": "John",
  "lastname": "Doe",
  "tokens": true,
  "sa": false
}
```

DELETE:  

```javascript
// Response
{ "delete": true }
```

#### /auth/user/{user}/group

_Only allowed by SuperAdmin_

| Method | Description |
|---|---|
| GET | List user group membership |
| POST | Add user group membership |

GET:  

```javascript
// Response
[
  {
    "id": "ab8bb53c-5cd1-43be-a0a0-7f41f9480bfa",
    "groupname": "Testgroup",
    "ldapcn": "cn=testgroup,cn=groups,cn=accounts,dc=example,dc=local",
    "radiusattr": "testgroup"
  },
  {..}
]
```

POST:  

```javascript
// Payload
{ 
  "groupname": "Testgroup",
  "ldapcn": "cn=testgroup,cn=groups,cn=accounts,dc=example,dc=local",
  "radiusattr": "testgroup"
}
// Response
{ 
  "id": "ab8bb53c-5cd1-43be-a0a0-7f41f9480bfa" 
}
```

#### /auth/user/{user}/group/{group}

_Only allowed by SuperAdmin_

| Method | Description |
|---|---|
| GET | Get group info |
| DELETE | Remove group membership |

GET:  

```javascript
// Response
{ 
  "groupname": "Testgroup",
  "ldapcn": "cn=testgroup,cn=groups,cn=accounts,dc=example,dc=local",
  "radiusattr": "testgroup"
}
```

DELETE:  

```javascript
// Response
{ "delete": true }
```

#### /auth/user/{user}/token

_Only allowed by Self and SuperAdmin_

| Method | Description |
|---|---|
| GET | List API tokens |
| POST | Create API token |

GET:  

```javascript
// Response
[
  { 
    "id": "efd34367-5aa6-49e6-bd99-fb6c5543ca4c",
    "name": "myTokenName",
    "expiry": "2024-01-01 12:00"
  },
  {..}
]
```

POST:  

```javascript
// Payload
{ 
  "name": "myTokenName",
  "expiry": "2024-01-01 12:00"
}
// Response
{ 
  "token": "nLO0v3OxbsRVyq6w58CyU8NZp1" 
}
```

#### /auth/user/{user}/token/{token}

_Only allowed by Self and SuperAdmin_

| Method | Description |
|---|---|
| GET | Get API token info |
| PUT | Update API token |
| DELETE | Delete API token |

GET:  

```javascript
// Response
{
  "name": "myTokenName",
  "expiry": "2999-12-31 23:59"
}
```

PUT:  

```javascript
// Payload
{
  "name": "myTokenName",
  "expiry": "2999-12-31 23:59"
}
```

DELETE:  

```javascript
// Response
{ "delete": true }
```

#### /auth/group

_Only allowed by SuperAdmin_

| Method | Description |
|---|---|
| GET | List group |
| POST | Create new group |

GET:  

```javascript
// Response
[
    {
    "id": "ab8bb53c-5cd1-43be-a0a0-7f41f9480bfa",
    "groupname": "Testgroup",
    "ldapcn": "cn=testgroup,cn=groups,cn=accounts,dc=example,dc=local",
    "radiusattr": "testgroup"
  },
  {..}
]
```

POST:  

```javascript
// Payload
{
  "groupname": "Testgroup",
  "ldapcn": "cn=testgroup,cn=groups,cn=accounts,dc=example,dc=local",
  "radiusattr": "testgroup"
}
// Response
{ "id": "ab8bb53c-5cd1-43be-a0a0-7f41f9480bfa" }
```

#### /auth/group/{group}

_Only allowed by SuperAdmin_

| Method | Description |
|---|---|
| GET | Get group info |
| PUT | Update group info |
| DELETE | Delete group |

GET:  

```javascript
// Response
{
  "groupname": "Testgroup",
  "ldapcn": "cn=testgroup,cn=groups,cn=accounts,dc=example,dc=local",
  "radiusattr": "testgroup"
}
```

PUT:  

```javascript
// Payload
{
  "groupname": "Testgroup",
  "ldapcn": "cn=testgroup,cn=groups,cn=accounts,dc=example,dc=local",
  "radiusattr": "testgroup"
}
```

DELETE:  

```javascript
// Response
{ "delete": true }
```
