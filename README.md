[![Build Status](https://travis-ci.org/ControleOnline/rest-essentials.svg)](https://travis-ci.org/ControleOnline/rest-essentials)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ControleOnline/rest-essentials/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/ControleOnline/rest-essentials/)
[![Code Coverage](https://scrutinizer-ci.com/g/ControleOnline/rest-essentials/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/ControleOnline/rest-essentials/)
[![Build Status](https://scrutinizer-ci.com/g/ControleOnline/rest-essentials/badges/build.png?b=master)](https://scrutinizer-ci.com/g/ControleOnline/rest-essentials/)

# REST Essentials #

This software aims to be engaged in any system and without any additional line programming is required, the final code is automatically optimized.

## Features ##
* Automatic create Entities from Doctrine
* Automatic create default routes to REST
* Automatic generate your Form to REST

## Installation ##
### Composer ###
Add these lines to your composer.json:

```
    "require": {
        "controleonline/rest-essentials": "*"        
    },
    "scripts": {
        "post-update-cmd": [
            "git describe --abbrev=0 --tags > .version"
        ]
    },

```


## Settings ##

**Default settings**
```
<?php
$config = array(
        'APP_ENV' => 'production', //Default configs to production or development
);
```

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
    'RESTEssentials' 
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

### JSON ###
Simply add the .json suffix at the end of the URL:
```
http://localhost/<Module>/<Controller>/<Action>.json?<Parameters>
http://localhost/<Entity>.json?<Parameters>
```

### FORM ###
Simply add the .form suffix at the end of the URL:
```
http://localhost/<Module>/<Controller>/<Action>.form?<Parameters>
http://localhost/<Entity>.form?<Parameters>
```
### HTML ###
Simply add the .html suffix at the end of the URL to set view terminal:
```
http://localhost/<Module>/<Controller>/<Action>.html?<Parameters>
http://localhost/<Entity>.html?<Parameters>
```
If you need to change the suffix, just change in the setting (config/application.config.local.php):
```
<?php
return array(
    'view' => array(
        'terminal_sufix' => array(            
            '.html',
            '.ajax' //Another extension
        )
    ),
    //Another configs
)
```

Do not forget to return a ViewModel on your controller:
```
        $view = new ViewModel();
        //Your code
        $this->_view->setVariables(\ControleOnline\Core\Helper\Format::returnData(array('Test')));
        return $view;
```
### REST ###
To return directly your Entity, use the REST standard
```
http://localhost/<Entity>/id/<ID>.json?<Parameters> //Find By ID
http://localhost/<Entity>.json?<Parameters> //Return all records
http://localhost/<Entity>/id/<ID>/<Children>.json?<Parameters> //Find By Parent ID
```
#### Pagination ####
```
http://localhost/<Entity>.json?page=2&limit=100 //Return second page limited by 100 records
```
#### Override methods ####
If the browser does not support PUT, DELETE and OPTIONS use :
```
http://localhost/<Entity>.json?method=PUT //Return second page limited by 100 records
```

#### Child Deep ####
To get more childs, add deep parameter on URL :
```
http://localhost/<Entity>.json?deep=10
```