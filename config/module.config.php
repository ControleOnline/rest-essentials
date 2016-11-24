<?php

return array(
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
                        'discoveryModule' => 'Core',
                        'module' => 'Home',
                        'controller' => 'Default',
                        'action' => 'index',
                        'base_url' => 'api',
                    ),
                ),
            )
        )
    )
);
