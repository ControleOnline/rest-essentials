<?php

namespace RESTEssentials;

use Zend\Mvc\ModuleRouteListener;
use Core\Helper\Url;

class Module {

    protected $sm;
    protected $config;
    protected $em;
    protected $controller;
    protected $module;

    public function getDefaultConfig($config) {
        $config['DefaultModule'] = isset($config['DefaultModule']) ? $config['DefaultModule'] : 'Home';
        $config['DefaultController'] = isset($config['DefaultController']) ? $config['DefaultController'] : 'Default';
        $config['LogChanges'] = isset($config['LogChanges']) ? $config['LogChanges'] : true;
        $config['EntityPath'] = isset($config['EntityPath']) ? $config['EntityPath'] : getcwd() . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
        return $config;
    }

    public function onBootstrap(\Zend\Mvc\MvcEvent $e) {
        $this->sm = $e->getApplication()->getServiceManager();
        $this->em = $this->sm->get('Doctrine\ORM\EntityManager');
        $config = $this->sm->get('config');

        $this->config = $this->getDefaultConfig(
                (isset($config['RESTEssentials']) ? $config['RESTEssentials'] : array())
        );
        $eventManager = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
        
    }


    public function init(\Zend\ModuleManager\ModuleManager $mm) {

        $config = $this->getDefaultConfig($this->config);
        $uri = array_values(array_filter(explode('/', $_SERVER['REQUEST_URI'])));
        if (isset($uri[0]) && isset($uri[1])) {
            $class = '\\' . ucfirst(Url::removeSufix($uri[0])) . '\\Controller\\' . ucfirst(Url::removeSufix($uri[1])) . 'Controller';
            $this->module = ucfirst(Url::removeSufix($uri[0]));
            $this->controller = $class;
        } elseif (isset($uri[0])) {
            $controller = $config['DefaultController'];
            $class = '\\' . ucfirst(Url::removeSufix($uri[0])) . '\\Controller\\' . $controller . 'Controller';
            $this->module = ucfirst(Url::removeSufix($uri[0]));
            $this->controller = $class;
        } else {
            $module = $config['DefaultModule'];
            $controller = $config['DefaultController'];
            $class = '\\' . $module . '\\Controller\\' . $controller . 'Controller';
            $this->module = $module;
            $this->controller = $class;
        }
    }


    public function getConfig() {
        $this->config = $this->getDefaultConfig(
                (isset($this->config['RESTEssentials']) ? $this->config['RESTEssentials'] : array())
        );
        $config = \Zend\Stdlib\ArrayUtils::merge(array('RESTEssentials' => $this->config), (include __DIR__ . '/config/module.config.php'));
        $config['doctrine']['driver']['Entity']['paths'][] = $this->config['EntityPath'];

        return $config;
    }

    public function getAutoloaderConfig() {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

}
