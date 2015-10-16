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
    }

```


### Settings ###

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
return array(
    'doctrine' => array(
        'connection' => array(
            'orm_default' => array(
                'driverClass' => 'Doctrine\DBAL\Driver\PDOMySql\Driver',
                'params' => array(
                    'host' => 'localhost',
                    'port' => '3306',
                    'user' => 'user',
                    'password' => 'pass',
                    'dbname' => 'db',
                    'driver' => 'pdo_mysql',
                    'charset' => 'utf8',//Very important
                    'driverOptions' => array(
                            1002=>'SET NAMES utf8' //Very important
                    )
                )
            )
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
In your module.config.php file:

```
<?php
namespace YourNameSpace;

return array(
    'RESTEssentials' => array(
            'EntityPath' => getcwd() . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR
        )
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
Simply add the .html suffix at the end of the URL:
```
http://localhost/<Module>/<Controller>/<Action>.html?<Parameters>
http://localhost/<Entity>.html?<Parameters>
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