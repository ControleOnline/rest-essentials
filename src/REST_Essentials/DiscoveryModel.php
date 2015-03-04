<?php

namespace REST_Essentials;

class DiscoveryModel {

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;
    private $params;
    private $method;
    private $rows;

    public function __construct($em, $method, $params) {
        $this->setEntityManager($em);
        $this->setMethod($method);
        $this->setParams($this->prepareParams($params, $method));
    }

    /**
     * Return a EntityManager
     *
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManager() {
        return $this->em;
    }

    public function getParams() {
        return $this->params;
    }

    public function setParams(array $params) {
        $this->params = $params;
    }

    public function getMethod() {
        return $this->method;
    }

    public function setMethod($method) {
        $this->method = $method;
    }

    public function setEntityManager(\Doctrine\ORM\EntityManager $em) {
        $this->em = $em;
    }

    public function prepareParams(\Zend\Http\PhpEnvironment\Request $params, $method = 'GET') {                
        
        $_params = array();
        switch ($method) {
            case 'PUT':
            case 'DELETE':                
                parse_str(file_get_contents('php://input'), $_params);
                array_merge($_params, $params->getPost()->toArray());
                break;
            case 'POST':                
                $_params = $params->getPost()->toArray();
                break;
            default:
                $_params = $params->getQuery()->toArray();
                break;
        }
        return $_params;
    }

    public function getTotalResults() {
        return $this->rows;
    }

    public function discovery($entity, $entity_parent = null) {

        $default_model = new Model\DefaultModel($this->getEntityManager());
        $default_model->setEntity('Entity\\' . $entity);

        switch ($this->getMethod()) {
            case 'POST':
                $data = $default_model->insert($this->params);
                break;
            case 'PUT':
                $data = $default_model->edit($this->params);
                break;
            case 'DELETE':
                $data = $default_model->delete($this->params['id']);
                break;
            default:
                $id = isset($this->params['id']) ? $this->params['id'] : null;
                $page = isset($this->params['page']) ? $this->params['page'] : 1;
                $limit = isset($this->params['limit']) ? $this->params['limit'] : 100;
                if ($entity_parent) {
                    $data = $default_model->getWithParent($id, $entity_parent, $page, $limit);
                } else {
                    $data = $default_model->get($id, $page, $limit);
                }
                $this->rows = $default_model->getTotalResults();
                break;
        }
        return $data;
    }

}
