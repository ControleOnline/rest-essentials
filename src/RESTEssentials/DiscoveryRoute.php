<?php

namespace RESTEssentials;

use Zend\Stdlib\RequestInterface as Request;

class DiscoveryRoute {

    protected $_controller;
    protected $_module;
    protected $_action;
    protected $_entity;
    protected $_vars;
    protected $_url;
    protected $_defaultRoute;
    protected $_EntityChildren;
    protected $_sufix = array('.json', '.form');

    public function __construct($defaultRoute) {
        $this->setDefaultRoute($defaultRoute);
    }

    public function getRoute() {
        $return = array_merge($this->getDefaultRoute(), array(
            'path' => implode('/', $this->getUrl())
        ));
        return $this->discoveryRoute($return);
    }

    public function setGetParams(Request $request) {
        $params = $this->getUrl();
        $qtde = count($params);
        for ($i = 0; $i < $qtde; $i += 2) {
            $key = str_replace($this->_sufix, '', $params[$i]);
            $value = isset($params[$i + 1]) ? str_replace($this->_sufix, '', $params[$i + 1]) : null;
            $request->getQuery()->set($key, $value);
        }
    }

    public function setMethod(Request $request, $uri) {
        $compare = '.form';
        $method = substr_compare($uri, $compare, strlen($uri) - strlen($compare), strlen($compare)) === 0;
        $is_form = $method ? 'form' : null;


        $compare = '.json';
        $method = substr_compare($uri, $compare, strlen($uri) - strlen($compare), strlen($compare)) === 0;
        $is_json = $method ? 'json' : null;

        $request->getQuery()->set('viewMethod', strtolower($is_form? : $is_json? : 'html'));
        $request->getQuery()->set('method', strtoupper($request->getQuery()->get('method')? : $request->getPost()->get('method')? : $_SERVER['REQUEST_METHOD']));
    }

    protected function formatClass($class, $type, $module = null) {
        if ($module) {
            return '\\' . $this->camelCase($module) . '\\' . $this->camelCase($type) . '\\' . $this->camelCase($class);
        } else {
            return '\\' . $this->camelCase($type) . '\\' . $this->camelCase($class);
        }
    }

    protected function discoveryByController($routes) {

        $defaultRoute = $this->getDefaultRoute();
        $module = $this->camelCase((isset($routes[0]) ? str_replace($this->_sufix, '', $routes[0]) : $defaultRoute['module']));
        $class_name = $this->camelCase((isset($routes[1]) ? str_replace($this->_sufix, '', $routes[1]) : $defaultRoute['controller']));
        $controller = $this->formatClass($class_name, 'Controller', $module) . 'Controller';

        if (class_exists($controller)) {
            $this->setModule($module);
            $this->setController($controller);
            $url = $this->getUrl();
            unset($url[0]);
            unset($url[1]);
            $this->setUrl($url);
        }
    }

    protected function discoveryByEntity($routes) {
        $defaultRoute = $this->getDefaultRoute();
        $entity = $this->camelCase((isset($routes[0]) ? str_replace($this->_sufix, '', $routes[0]) : null));
        $this->setModule($defaultRoute['module']);
        $this->setController($this->formatClass($defaultRoute['controller'], 'Controller', $defaultRoute['module']));
        $this->setAction($defaultRoute['action']);

        if ($entity) {
            $class_name = $this->formatClass($entity, 'Entity');
            $this->setEntity($entity);
            if (class_exists($class_name)) {
                $url = $this->getUrl();
                unset($url[0]);
                $this->setUrl($url);
            }
        }
    }

    protected function discoveryAction($routes) {
        $defaultRoute = $this->getDefaultRoute();
        $action = lcfirst($this->camelCase((isset($routes[2]) ? $routes[2] : $defaultRoute['action'])));
        $class = $this->getController();
        $testClass = new $class();
        if (method_exists($testClass, $action . 'Action')) {
            $this->setAction($action);
            $url = $this->getUrl();
            unset($url[0]);
            $this->setUrl($url);
        } else {
            $this->setAction($defaultRoute['action']);
        }
    }

    protected function discoveryEntityChildren() {
        $routes = $this->getUrl();
        $count = count($routes);
        if ($count % 2 != 0 && $count > 0) {
            $this->setEntityChildren(str_replace($this->_sufix, '', $routes[$count - 1]));
            unset($routes[$count - 1]);
            $this->setUrl($routes);
        }
    }

    protected function discoveryRoute($default) {
        $routes = $this->getUrl();
        $this->discoveryByController($routes);
        if ($this->getController()) {
            $this->discoveryAction($routes);
        } else {
            $this->discoveryByEntity($routes);
            $this->discoveryEntityChildren();
        }

        $return = array(
            'module' => $this->camelCase($this->getModule()),
            'controller' => $this->getController(),
            'action' => $this->camelCase($this->getAction()),
            'entity' => $this->camelCase($this->getEntity()),
            'entity_children' => $this->camelCase($this->getEntityChildren())
        );
        return array_merge($default, $return);
    }

    protected function camelCase($string) {
        return str_replace(' ', '', ucfirst($string));
    }

    public function getController() {
        return $this->_controller;
    }

    public function getModule() {
        return $this->_module;
    }

    public function getAction() {
        return $this->_action;
    }

    public function getEntity() {
        return $this->_entity;
    }

    public function getVars() {
        return $this->_vars;
    }

    public function getUrl() {
        return array_values($this->_url);
    }

    public function setController($controller) {
        $this->_controller = $controller;
        return $this;
    }

    public function setModule($module) {
        $this->_module = $module;
        return $this;
    }

    public function setAction($action) {
        $this->_action = $action;
        return $this;
    }

    public function setEntity($entity) {
        $this->_entity = $entity;
        return $this;
    }

    public function setVars($vars) {
        $this->_vars = $vars;
        return $this;
    }

    public function setUrl($url) {
        if (is_array($url)) {
            $routes = $url;
        } else {
            $routes = array_filter(explode('/', $url));
        }
        $this->_url = array_values($routes);
        return $this;
    }

    public function getDefaultRoute() {
        return $this->_defaultRoute;
    }

    public function setDefaultRoute($defaultRoute) {
        $this->_defaultRoute = $defaultRoute;
        return $this;
    }

    public function getEntityChildren() {
        return $this->_EntityChildren;
    }

    public function setEntityChildren($EntityChildren) {
        $this->_EntityChildren = $EntityChildren;
        return $this;
    }

}
