<?php

$path = getcwd() . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;


return array(
    'RESTEssentials' => array(
        'EntityPath' => $path,
        'LogChanges' => true
    ),
    'controllers' => array(
        'invokables' => array(
            'RESTEssentials\Controller\Default' => 'RESTEssentials\Controller\DefaultController',
        ),
    ),
    'router' => array(
        'routes' => array(
            'default' => array(
                'type' => 'RESTEssentials\RESTEssentials',
                'options' => array(
                    'route' => '/[:module][/:controller[/:action]]',
                    'constraints' => array(
                        'module' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ),
                    'defaults' => array(
                        'module' => 'RESTEssentials',
                        'controller' => 'Default',
                        'action' => 'index',
                    ),
                ),
            )
        )
    ),
    // Doctrine configuration
    'doctrine' => array(
        'driver' => array(
            'Entity' => array(
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => array($path)
            ),
            'orm_default' => array(
                'drivers' => array(
                    'Entity' => 'Entity'
                ),
            ),
        ),
    ),
    'view_manager' => array(
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
    ),
);
