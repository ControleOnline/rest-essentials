<?php

return array(
    'controllers' => array(
        'invokables' => array(
            'RESTEssentials\Controller\Default' => 'RESTEssentials\Controller\DefaultController',
        ),
    ),
    'router' => array(
        'routes' => array(
            'home' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route' => '/',
                    'defaults' => array(
                        'controller' => 'Home\Controller\Default',
                        'action' => 'index',
                    ),
                ),
            ),
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
                        'discoveryModule' => 'RESTEssentials',
                        'module' => 'Home',
                        'controller' => 'Default',
                        'action' => 'index',
                    ),
                ),
            )
        )
    ),
    'doctrine' => array(
        'driver' => array(
            'Entity' => array(
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array'
            ),
            'orm_default' => array(
                'drivers' => array(
                    'Entity' => 'Entity'
                ),
            ),
        ),
    )
);
