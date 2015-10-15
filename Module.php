<?php

namespace RESTEssentials;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\Http\Response;
use Zend\Json\Json;

class Module {

    protected $sm;
    protected $config;
    protected $em;
    protected $controller;

    public function getDefaultConfig($config) {
        $config['LogChanges'] = isset($config['LogChanges']) ? $config['LogChanges'] : true;
        $config['EntityPath'] = isset($config['EntityPath']) ? $config['EntityPath'] : getcwd() . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
        return $config;
    }

    public function onBootstrap(\Zend\Mvc\MvcEvent $e) {

        $this->sm = $e->getApplication()->getServiceManager();
        $this->em = $this->sm->get('Doctrine\ORM\EntityManager');
        $config = $this->sm->get('config');

        $this->config = $this->getDefaultConfig(
                (isset($config['RESTEssentials']) ? : array())
        );
        $eventManager = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
        $this->setResponseType($e);
        if (isset($config['doctrine']['connection']['orm_default']['params'])) {
            $dbConfig = $config['doctrine']['connection']['orm_default']['params'];
            $entity = new DiscoveryEntity($this->em, $dbConfig, $config['RESTEssentials']);
            $entity->checkEntities();
        }
    }

    public function init(\Zend\ModuleManager\ModuleManager $mm) {
        $uri = array_values(array_filter(explode('/', $_SERVER['REQUEST_URI'])));
        if (isset($uri[0]) && isset($uri[1])) {
            $module = explode('.', ucfirst($uri[0]));
            $controller = explode('.', ucfirst($uri[1]));
            $class = '\\' . $module[0] . '\\Controller\\' . $controller[0] . 'Controller';
            $this->controller = $class;
        }
    }

    public function getControllerConfig() {
        return array(
            'invokables' => array(
                $this->controller => $this->controller
            )
        );
    }

    public function finishJsonStrategy(\Zend\Mvc\MvcEvent $e) {

        $response = new Response();
        $response->getHeaders()->addHeaderLine('Content-Type', 'application/json; charset=utf-8');
        $response->setContent(Json::encode($e->getResult()->getVariables(), true));
        $e->setResponse($response);
    }

    public function setResponseType(\Zend\Mvc\MvcEvent $e) {
        $this->verifyJsonStrategy($e);
    }

    public function verifyJsonStrategy(\Zend\Mvc\MvcEvent $e) {
        $request = $e->getRequest();
        $headers = $request->getHeaders();
        $uri = $request->getUri()->getPath();
        $compare = '.json';
        $is_json = substr_compare($uri, $compare, strlen($uri) - strlen($compare), strlen($compare)) === 0;
        if ($headers->has('accept') || $is_json) {
            $accept = $headers->get('accept');
            $match = $accept->match('application/json');
            if ($match && $match->getTypeString() != '*/*' || $is_json) {
                $e->getApplication()->getEventManager()->attach('render', array($this, 'registerJsonStrategy'), 100);
                $e->getApplication()->getEventManager()->attach(MvcEvent::EVENT_FINISH, array($this, 'finishJsonStrategy'));
                return true;
            } else {
                return false;
            }
        }
        return false;
    }

    public function registerJsonStrategy(\Zend\Mvc\MvcEvent $e) {
        $app = $e->getTarget();
        $locator = $app->getServiceManager();
        $view = $locator->get('Zend\View\View');
        $jsonStrategy = $locator->get('ViewJsonStrategy');
        $view->getEventManager()->attach($jsonStrategy, 100);
    }

    public function getConfig() {
        $this->config = $this->getDefaultConfig(
                (isset($this->config['RESTEssentials']) ? : array())
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
