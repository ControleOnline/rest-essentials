<?php

namespace RESTEssentials;

use Zend\Mvc\Router\Http\RouteInterface;
use Zend\Stdlib\RequestInterface as Request;
use Zend\Stdlib\ArrayUtils;
use Zend\Mvc\Router\Exception\InvalidArgumentException;
use Zend\Mvc\Router\Http\RouteMatch;
use RESTEssentials\DiscoveryRestRoute;

class RESTEssentials implements RouteInterface {

    protected $defaults = array();

    public function __construct(array $defaults = array()) {
        $this->defaults = $defaults;
    }

    public static function factory($options = array()) {
        if ($options instanceof \Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        } elseif (!is_array($options)) {
            throw new InvalidArgumentException(__METHOD__ . ' expects an array or Traversable set of options');
        }

        if (!isset($options['defaults'])) {
            $options['defaults'] = array();
        }

        return new static($options['defaults']);
    }

    public function match(Request $request, $pathOffset = null) {
        if (!method_exists($request, 'getUri')) {
            return null;
        }
        $uri = $request->getUri();
        $fullPath = $uri->getPath();
        $path = substr($fullPath, $pathOffset);
        $alias = trim($path, '/');
        $discovery = new DiscoveryRestRoute($this->defaults);
        $discovery->setUrl($alias);
        $options = $discovery->getRoute();
        $discovery->setGetParams($request);        
        $discovery->setMethod($request, $uri);        
        return new RouteMatch($options);
    }

    public function assemble(array $params = array(), array $options = array()) {

        if (array_key_exists('path', $params)) {
            return '/' . $params['path'];
        }

        return '/';
    }

    public function getAssembledParams() {
        return array();
    }

}
