[![Build Status](https://travis-ci.org/ControleOnline/user.svg)](https://travis-ci.org/ControleOnline/user)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ControleOnline/user/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/ControleOnline/user/)
[![Code Coverage](https://scrutinizer-ci.com/g/ControleOnline/user/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/ControleOnline/user/)
[![Build Status](https://scrutinizer-ci.com/g/ControleOnline/user/badges/build.png?b=master)](https://scrutinizer-ci.com/g/ControleOnline/user/)

# User management and login #


## Features ##
* User create account
* User login
* Store user session on database (Common on balanced servers)

## Installation ##
### Composer ###
Add these lines to your composer.json:

```
    "require": {
        "controleonline/user": "*"        
    }
```


## Settings ##

### Configure DB ###
In your config/autoload/database.local.php confiruration add the following:

```
<?php
$db = array(
    'host' => 'localhost',
    'port' => '3306',
    'user' => 'user',
    'password' => 'pass',
    'dbname' => 'db',
    'driver' => 'pdo_mysql',
    'init_command' => 'SET NAMES utf8',
    'port' => '3306'
);
return array(
    'db' => array( //Use on zend session to store session on database (common on balanced web servers)
        'driver' => $db['driver'],
        'dsn' => 'mysql:dbname=' . $db['dbname'] . ';host=' . $db['host'],
        'username' => $db['user'],
        'password' => $db['password'],
        'driver_options' => array(
            PDO::MYSQL_ATTR_INIT_COMMAND => $db['init_command'],
            'buffer_results' => true
        ),
    ),
    'doctrine' => array(
        'connection' => array(
            'orm_default' => array(
                'driverClass' => 'Doctrine\DBAL\Driver\PDOMySql\Driver',
                'params' => array(
                    'host' => $db['host'],
                    'port' => $db['port'],
                    'user' => $db['user'],
                    'password' => $db['password'],
                    'dbname' => $db['dbname'],
                    'driver' => $db['driver'],
                    'charset' => 'utf8', //Very important
                    'driverOptions' => array(
                        1002 => $db['init_command'] //Very important
                    )
                )
            )
        )
    )
);
```
### Configure Session ###
In your config/autoload/session.global.php confiruration add the following:

```
<?php
return array(
    'session' => array(
        'sessionConfig' => array(
            'cache_expire' => 86400,
            'cookie_domain' => 'localhost',
            'name' => 'localhost',
            'cookie_lifetime' => 1800,
            'gc_maxlifetime' => 1800,
            'cookie_path' => '/',
            'cookie_secure' => TRUE,
            'remember_me_seconds' => 3600,
            'use_cookies' => true,
        ),
        'serviceConfig' => array(
            'base64Encode' => false
        )
    )
);
```

### Zend 2 ###
In your config/application.config.php confiruration add the following:

```
<?php
$modules = array(
    'User' 
);
return array(
    'modules' => $modules,
    'module_listener_options' => array(
        'module_paths' => array(
            './module',
            './vendor',
        ),
        'config_glob_paths' => array(
            'config/autoload/{,*.}{global,local}.php',
        ),
    ),
);
```
## Usage ##

### Create Account ###
```
http://localhost/user/create-account
```

### Login ###
```
http://localhost/user/login
```
### Logout ###
```
http://localhost/user/logout
```
### Forgot Password ###
```
http://localhost/user/forgot-password
```
### Forgot Username ###
```
http://localhost/user/forgot-username
```

### Change User ###
```
http://localhost/user/change-user
```