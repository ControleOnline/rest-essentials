<?php

namespace REST_Essentials\Model;

use Doctrine\ORM\Tools\Pagination\Paginator;

class DefaultModel {

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var \Doctrine\ORM\EntityRepository          
     */
    private $entity;
    private $entity_name;
    private $rows;

    public function __construct($em) {
        $this->em = $em;
    }

    public function setEntity($entity) {
        $this->entity = $this->em->getRepository($entity);
        $this->entity_name = $entity;
    }

    public function getEntity() {
        return $this->entity;
    }

    public function delete($id) {
        $entity = $this->entity->find($id);
        if ($entity) {
            $this->em->remove($entity);
            $this->em->flush();
            return true;
        } else {
            return false;
        }
    }

    public function edit(array $params) {
        if (isset($params['id'])) {
            $entity = $this->entity->find($params['id']);
            if (isset($entity) && $entity) {
                $entity = $this->setData($entity, $params);
                $this->em->persist($entity);
                $this->em->flush();
                return true;
            } else {
                return false;
            }
        }
    }

    public function insert(array $params) {
        $class = new $this->entity_name;
        $entity = $this->setData($class, $params);
        $this->em->persist($entity);
        $this->em->flush();
        return array('id' => $entity->getId());
    }

    public function setData($entity, $params) {
        $field_names = $this->getFieldNames()? : array();
        foreach ($field_names as $field) {
            if ($field != 'id' && isset($params[$field])) {
                $f = 'set' . ucfirst($field);
                $entity->$f($params[$field]);
            }
        }

        $field_a_names = $this->getAssociationNames()? : array();
        foreach ($field_a_names as $field_a) {
            if (isset($params[$field_a . '_id'])) {
                $f_a = ucfirst($field_a);
                $object = $this->em->getRepository('Entity\\' . $f_a)->find($params[$field_a . '_id']);
                $f_s = 'set' . $f_a;
                $entity->$f_s($object);
            }
        }
        return $entity;
    }

    public function getTotalResults() {
        return $this->rows;
    }

    public function getAssociationNames() {
        return $this->em->getClassMetadata($this->entity_name)->getAssociationNames();
    }

    public function getFieldNames() {
        return $this->em->getClassMetadata($this->entity_name)->getFieldNames();
    }

    public function getWithParent($id, $entity_parent, $page = 1, $limit = 100) {
        $table = $this->em->getClassMetadata($this->entity_name)->getTableName();
        $qbp = $this->em->getRepository('Entity\\' . ucfirst($entity_parent))->createQueryBuilder('e')->select('e');
        $qb = $this->entity->createQueryBuilder('e')->select('e');
        $parent = strtolower($entity_parent);
        $data[$parent] = $qbp->where('e.id=' . $id)->getQuery()->getArrayResult()[0];
        $data[$parent][strtolower($table)] = $qb->where('e.' . $parent . '=' . $id)->getQuery()->getArrayResult();
        $query = $qb->getQuery()->setFirstResult($limit * ($page - 1))->setMaxResults($limit);
        $paginator = new Paginator($query);
        $this->rows = count($paginator);
        return $data;
    }

    public function get($id = null, $page = 1, $limit = 100) {
        $qb = $this->entity->createQueryBuilder('e')->select('e');
        if ($id) {
            return $qb->where('e.id=' . $id)->getQuery()->getArrayResult();
        } else {
            $query = $qb->getQuery()->setFirstResult($limit * ($page - 1))->setMaxResults($limit);
            $paginator = new Paginator($query);
            $this->rows = count($paginator);
            return $query->getArrayResult();
        }
    }

    public function toArray($data) {
        $hydrator = new \DoctrineModule\Stdlib\Hydrator\DoctrineObject($this->em, $this->entity_name);
        return $hydrator->extract($data);
    }

}
