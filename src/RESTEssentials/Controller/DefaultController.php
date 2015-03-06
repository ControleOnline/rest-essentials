<?php

namespace RESTEssentials\Controller;

use RESTEssentials\DiscoveryModel;
use Zend\View\Model\ViewModel;

class DefaultController extends \Zend\Mvc\Controller\AbstractActionController {

    /**
     * @var Doctrine\ORM\EntityManager
     */
    protected $_em;
    protected $_allowed_methods = array('GET', 'POST', 'PUT', 'DELETE', 'FORM');
    protected $_method;
    protected $_model;
    protected $_view;
    protected $_entity_children;
    protected $_entity;

    private function initialize() {
        $method_request = strtoupper($this->params()->fromQuery('method') ? : filter_input(INPUT_SERVER, 'REQUEST_METHOD'));
        $this->_method = in_array($method_request, $this->_allowed_methods) ? $method_request : 'GET';
        $this->_model = new DiscoveryModel($this->getEntityManager(), $this->_method, $this->getRequest());
        $this->_view = new ViewModel();
        $this->_entity_children = $this->params('entity_children');
        $this->_entity = $this->params('entity');
    }

    public function setEntityManager(\Doctrine\ORM\EntityManager $em) {
        $this->_em = $em;
    }

    /**
     * Return a EntityManager
     *
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManager() {
        if (null === $this->_em) {
            $this->_em = $this->getServiceLocator()->get('\Doctrine\ORM\EntityManager');
        }
        return $this->_em;
    }

    private function getForm() {
        $return = [];
        $id = $this->params()->fromQuery('id');
        $this->_model->setMethod('FORM');
        $return['form']['method'] = $id ? 'PUT' : 'POST';
        $return['form']['action'] = $this->getRequest()->getUri();
        if ($this->_entity) {
            $return['form'] = $this->_model->discovery($this->_entity);
        }
        if ($this->_entity_children) {
            $return['form']['children'] = $this->_model->discovery($this->_entity_children, $this->_entity);
        }
        return $return;
    }

    private function alterData() {
        $return = [];
        $data = $this->_model->discovery($this->_entity);
        if ($data) {
            $return['success'] = true;
        } else {
            $return['error']['code'] = 0;
            $return['error']['message'] = 'No register with this ID';
            $return['success'] = false;
        }
        return $return;
    }

    private function insertData() {
        $data = $this->_model->discovery($this->_entity);
        $return = array(
            'data' => $data
        );
        return $return;
    }

    private function getDataById($id) {
        $return = [];
        $page = $this->params()->fromQuery('page') ? : 1;
        if ($this->_entity_children) {
            $this->_model->setMethod('GET');
            $data = $this->_model->discovery($this->_entity_children, $this->_entity);
            $return = array(
                'data' => $data,
                'count' => isset($data[strtolower($this->_entity)][strtolower($this->_entity_children)]) ? count($data[strtolower($this->_entity)][strtolower($this->_entity_children)]) : 0,
                'total' => (int) $this->_model->getTotalResults(),
                'page' => (int) $page
            );
        } elseif ($id) {
            $data = $this->_model->discovery($this->_entity);
            $return = array(
                'data' => $data
            );
        }
        return $return;
    }

    private function getAllData() {
        $page = $this->params()->fromQuery('page') ? : 1;
        $data = $this->_model->discovery($this->_entity);
        $return = array(
            'data' => $data,
            'count' => count($data),
            'total' => (int) $this->_model->getTotalResults(),
            'page' => (int) $page
        );
        return $return;
    }

    private function getData() {
        $id = $this->params()->fromQuery('id');
        if ($id) {
            $return = $this->getDataById($id);
        } else {
            $return = $this->getAllData();
        }
        return $return;
    }

    public function indexAction() {
        $this->initialize();
        $return = [];
        try {
            switch ($this->_method) {
                case 'FORM':
                    $this->_view->setTerminal(true);
                    $return = $this->getForm();
                    break;
                case 'DELETE':
                case 'PUT':
                    $return = $this->alterData();
                case 'POST':
                    $return = $this->insertData();
                case 'GET':
                    $return = $this->getData();
            }
            $return['method'] = $this->_method;
            $return['success'] = isset($return['success']) ? $return['success'] : true;
        } catch (\Exception $e) {
            $return = array('error' => array('code' => $e->getCode(), 'message' => $e->getMessage(),), 'success' => false);
        }
        $this->_view->setVariables($return);
        return $this->_view;
    }

}
