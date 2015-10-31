<?php

namespace RESTEssentials;

use Zend\Mvc\ModuleRouteListener;

class Module {

    protected $sm;
    protected $config;
    protected $em;

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
