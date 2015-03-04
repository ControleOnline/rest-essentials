<?php

namespace RESTEssentials\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Doctrine\ORM\EntityManager;
use RESTEssentials\DiscoveryModel;
use \Zend\View\Model\ViewModel;

class DefaultController extends AbstractActionController {

    /**
     * @var Doctrine\ORM\EntityManager
     */
    protected $em;

    public function setEntityManager(EntityManager $em) {
        $this->em = $em;
    }

    /**
     * Return a EntityManager
     *
     * @return Doctrine\ORM\EntityManager
     */
    public function getEntityManager() {
        if (null === $this->em) {
            $this->em = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
        }
        return $this->em;
    }

    public function indexAction() {              
        try {
            $allowed_methods = array('GET', 'POST', 'PUT', 'DELETE');
            $method_request = strtoupper($this->params()->fromQuery('method') ? : $_SERVER['REQUEST_METHOD']);
            $method = in_array($method_request, $allowed_methods) ? $method_request : 'GET';
            $DiscoveryModel = new DiscoveryModel($this->getEntityManager(), $method, $this->getRequest());

            switch ($method) {
                case 'DELETE':
                case 'PUT':
                    $data = $DiscoveryModel->discovery($this->params('entity'));
                    if ($data) {
                        $return['success'] = true;
                    } else {
                        $return['error']['code'] = 0;
                        $return['error']['message'] = 'No register with this ID';
                        $return['success'] = false;
                    }
                    break;
                case 'POST':
                    $data = $DiscoveryModel->discovery($this->params('entity'));
                    $return = array(
                        'data' => $data
                    );
                    break;
                case 'GET':
                    $page = $this->params()->fromQuery('page') ? : 1;
                    $entity_children = $this->params('entity_children');
                    $id = $this->params()->fromQuery('id');
                    if ($entity_children && $id) {
                        $data = $DiscoveryModel->discovery($entity_children, $this->params('entity'));
                    } else {
                        $data = $DiscoveryModel->discovery($this->params('entity'));
                    }

                    if ($entity_children && $id && $data) {
                        $total = $DiscoveryModel->getTotalResults();
                        $return = array(
                            'data' => $data,
                            'count' => count($data[strtolower($this->params('entity'))][strtolower($entity_children)]),
                            'total' => (int) $total,
                            'page' => (int) $page
                        );
                    } elseif ($id && $data) {
                        $return = array(
                            'data' => $data
                        );
                    } else {
                        $total = $DiscoveryModel->getTotalResults();
                        $return = array(
                            'data' => $data,
                            'count' => count($data),
                            'total' => (int) $total,
                            'page' => (int) $page
                        );
                    }
                    break;
            }
            $return['method'] = $method;
            $return['success'] = isset($return['success']) ? $return['success'] : true;
            return new ViewModel($return);
        } catch (\Exception $e) {
            $return = array(
                'error' => array(
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                ),
                'success' => false
            );
            return new ViewModel($return);
        }
    }

}
