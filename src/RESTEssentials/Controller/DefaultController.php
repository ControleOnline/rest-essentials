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
    protected $_allowed_viewMethods = array('json', 'html', 'form');
    protected $_method;
    protected $_viewMethod;
    protected $_model;
    protected $_view;
    protected $_entity_children;
    protected $_entity;

    private function initialize() {
        $method_request = strtoupper($this->params()->fromQuery('method') ? : filter_input(INPUT_SERVER, 'REQUEST_METHOD'));
        $viewMethod_request = strtolower($this->params()->fromQuery('viewMethod'));
        $this->_method = in_array($method_request, $this->_allowed_methods) ? $method_request : 'GET';
        $this->_viewMethod = in_array($viewMethod_request, $this->_allowed_viewMethods) ? $viewMethod_request : 'html';
        $this->_model = new DiscoveryModel($this->getEntityManager(), $this->_method, $this->_viewMethod, $this->getRequest());
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
        $this->_model->setViewMethod('form');
        $this->_view->setTerminal(true);
        if ($this->_entity) {
            $return = $this->_model->discovery($this->_entity);
        }
        if ($this->_entity_children) {
            $return['id'] = $id;
            $return['children'] = $this->_model->discovery($this->_entity_children, $this->_entity);
        }
        $return['method'] = $id ? 'PUT' : 'POST';
        $return['action'] = $this->getRequest()->getUri();
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
                'count' => isset($data[strtolower($this->_entity)][0][strtolower($this->_entity_children)]) ? count($data[strtolower($this->_entity)][0][strtolower($this->_entity_children)]) : 0,
                'total' => (int) $this->_model->getTotalResults(),
                'page' => (int) $page
            );
        } elseif ($id) {
            $data = $this->_model->discovery($this->_entity);
            $return = array(
                'data' => $data,
                'count' => isset($data[strtolower($this->_entity)]) ? count($data[strtolower($this->_entity)]) : 0,
                'total' => (int) $this->_model->getTotalResults(),
                'page' => (int) $page
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
        $return = array('response' => []);
        try {
            switch ($this->_method) {
                case 'DELETE':
                case 'PUT':
                    $return['response'] = ($this->_viewMethod == 'form') ? [] : $this->alterData();
                case 'POST':
                    $return['response'] = ($this->_viewMethod == 'form') ? [] : $this->insertData();
                case 'GET':
                    $return['response'] = ($this->_viewMethod == 'form') ? [] : $this->getData();
            }

            switch ($this->_viewMethod) {
                case 'form':
                    $return['response']['form'] = $this->getForm();
                    break;
            }

            $return['response']['method'] = $this->_method;
            $return['response']['view_method'] = $this->_viewMethod;

            $return['response']['success'] = isset($return['success']) ? $return['success'] : true;
        } catch (\Exception $e) {
            $return = array('response' => array('error' => array('code' => $e->getCode(), 'message' => $e->getMessage()), 'success' => false));
        }
        $this->_view->setVariables($return);
        return $this->_view;
    }

}
