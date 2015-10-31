<?php

namespace RESTEssentials;

use Core\DiscoveryRoute;
use Core\Helper\Url;

class DiscoveryRestRoute extends DiscoveryRoute {

    protected function discoveryRoute($default) {
        $routes = $this->getUrl();
        $this->discoveryByController($routes);                
        if ($this->getController()) {            
            $this->discoveryAction();
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

    protected function discoveryByEntity($routes) {

        $defaultRoute = $this->getDefaultRoute();
        $entity = $this->camelCase((isset($routes[0]) ? Url::removeSufix($routes[0]) : null));
        $this->setModule($defaultRoute['discoveryModule']);
        $this->setController($this->formatClass($defaultRoute['controller'], 'Controller', $defaultRoute['discoveryModule']));
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

    protected function discoveryEntityChildren() {
        $routes = $this->getUrl();
        $count = count($routes);
        if ($count % 2 != 0 && $count > 0) {
            $this->setEntityChildren(Url::removeSufix($routes[$count - 1]));
            unset($routes[$count - 1]);
            $this->setUrl($routes);
        }
    }

}
