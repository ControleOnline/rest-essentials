<?php

namespace RESTEssentials;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\Http\Response;
use Zend\Json\Json;
use Zend\View\Resolver\TemplatePathStack;
use Zend\View\Resolver\TemplateMapResolver;
use RESTEssentials\Helper\Url;

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
        $this->setResponseType($e);
        if (isset($config['doctrine']['connection']['orm_default']['params'])) {
            $dbConfig = $config['doctrine']['connection']['orm_default']['params'];
            $entity = new DiscoveryEntity($this->em, $dbConfig, $config['RESTEssentials']);
            $entity->checkEntities();
        }
        $this->configDefaultViewOptions($eventManager);
    }

    private function configDefaultViewOptions($eventManager) {
        $eventManager->attach(MvcEvent::EVENT_RENDER, function(MvcEvent $event) {

            $baseDir = getcwd() . DIRECTORY_SEPARATOR . 'module' . DIRECTORY_SEPARATOR . $this->module . DIRECTORY_SEPARATOR . 'view';

            $sm = $event->getParam('application')->getServiceManager();
            /** @var TemplateMapResolver $viewResolverMap */
            $viewResolverMap = $sm->get('ViewTemplateMapResolver');

            if (is_file($baseDir . '/layout/layout.phtml')) {
                $viewResolverMap->add('layout/layout', $baseDir . '/layout/layout.phtml');
            }
            if (is_file($baseDir . '/error/404.phtml')) {
                $viewResolverMap->add('error/404', $baseDir . '/error/404.phtml');
            }
            if (is_file($baseDir . '/error/index.phtml')) {
                $viewResolverMap->add('error/index', $baseDir . '/error/index.phtml');
            }
            /** @var TemplatePathStack $viewResolverPathStack */
            $viewResolverPathStack = $sm->get('ViewTemplatePathStack');
            $viewResolverPathStack->addPath($baseDir);
            $viewResolverPathStack->addPath(__DIR__ . DIRECTORY_SEPARATOR . 'view');
        }, 10);
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
                (isset($this->config['RESTEssentials']) ? $config['RESTEssentials'] : array())
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
