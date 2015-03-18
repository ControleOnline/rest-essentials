<?php

namespace RESTEssentials\Model;

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
    private $children_entity_name;
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

    public function getMetadata() {
        $cmf = new \Doctrine\ORM\Tools\DisconnectedClassMetadataFactory();
        $cmf->setEntityManager($this->em);
        return $cmf->getMetadataFor($this->entity_name);
    }

    public function form($entity) {
        $return = [];
        $return['form_name'] = strtolower($entity);
        $metadata = $this->getMetadata();
        if ($metadata->fieldMappings) {
            $return['fields'] = $metadata->fieldMappings;
        }
        $assoc = $this->getAssociationNames();
        if ($assoc) {
            $return['assoc'] = $assoc;
        }
        return $return;
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

    private function getChilds(\Doctrine\ORM\QueryBuilder &$qb, $entity_name, array $alias, $join_alias, $parent = null, &$deep = 0, &$join = array()) {
        if ($deep < 50) {
            $childs = $this->em->getClassMetadata($entity_name)->getAssociationMappings();
            foreach ($childs as $key => $child) {
                if (($parent || (!$parent && $deep == 0)) && $child['targetEntity'] && !in_array($child['targetEntity'], $join)) {
                    $deep ++;
                    $join[] = $child['targetEntity'];
                    $j = $this->generateAlias();
                    $table = strtolower(str_replace('Entity\\', '', $child['targetEntity']));
                    $alias[] = $j;
                    $qb->select($alias);
                    $qb->leftJoin($join_alias . '.' . $table, $j);
                    $table_child = $this->em->getClassMetadata('Entity\\' . ucfirst($table))->getAssociationMappings();
                    foreach ($table_child as $k => $p) {
                        $this->getChilds($qb, 'Entity\\' . ucfirst($table), $alias, $j, 'Entity\\' . ucfirst($k), $deep, $join);
                    }
                }
            }
        }
    }

    private function generateAlias($lenght = 10) {
        return substr(str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $lenght);
    }

    public function getWithParent($id, $entity_parent, $page = 1, $limit = 100) {

        $this->children_entity_name = $this->entity_name;
        $this->entity_name = $entity_parent;
        $alias_parent = $this->generateAlias();
        $qb = $this->em->getRepository('Entity\\' . ucfirst($entity_parent))->createQueryBuilder($alias_parent)->select($alias_parent);
        $join = array('Entity\\' . ucfirst($entity_parent));
        $deep = 0;
        $this->getChilds($qb, 'Entity\\' . ucfirst($this->entity_name), array($alias_parent), $alias_parent, null, $deep, $join);
        $qb->where($alias_parent . '.id=' . $id);
        $query = $qb->setFirstResult($limit * ($page - 1))->setMaxResults($limit)->getQuery();
        $paginator = new Paginator($query);
        $data[strtolower($this->entity_name)] = $query->getArrayResult();
        $this->rows = $paginator->count();
        return $data;
    }

    public function get($id = null, $page = 1, $limit = 100) {
        $alias = $this->generateAlias();
        $qb = $this->entity->createQueryBuilder($alias)->select($alias);
        if ($id) {
            $qb->where($alias . '.id=' . $id);
        }
        $join = array($this->entity_name);
        $deep = 0;
        $this->getChilds($qb, $this->entity_name, array($alias), $alias, null, $deep, $join);
        $query = $qb->getQuery()->setFirstResult($limit * ($page - 1))->setMaxResults($limit);
        $data[strtolower(str_replace('Entity\\', '', $this->entity_name))] = $query->getArrayResult();
        $paginator = new Paginator($query);
        $this->rows = $paginator->count();        
        return $data;
    }

    public function toArray($data) {
        $hydrator = new \DoctrineModule\Stdlib\Hydrator\DoctrineObject($this->em, $this->entity_name);
        return $hydrator->extract($data);
    }

}
