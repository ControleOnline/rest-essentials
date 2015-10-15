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
    private $alias = [];
    private $join = [];
    private $current_deep = 0;
    private $max_deep = 1;

    public function __construct($em) {
        $this->em = $em;
    }

    public function getMax_deep() {
        return $this->max_deep;
    }

    public function setMax_deep($max_deep) {
        $this->max_deep = $max_deep;
        return $this;
    }

    public function setEntity($entity) {
        $this->entity = $this->em->getRepository($entity);
        $this->entity_name = $entity;
        return $this;
    }

    public function getEntity() {
        return $this->entity;
    }

    public function getMetadata() {
        $cmf = new \Doctrine\ORM\Tools\DisconnectedClassMetadataFactory();
        $cmf->setEntityManager($this->em);
        return $cmf->getMetadataFor($this->entity_name);
    }

    public function form($entity, $params = false) {
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
        $data = (isset($params['id']) && $params['id']) ? $this->get($params['id']) : null;
        $return['data'] = isset($data[strtolower($entity)]) ? $data[strtolower($entity)][0] : null;
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

    private function getChilds(\Doctrine\ORM\QueryBuilder &$qb, $entity_name, $join_alias) {
        if ($this->current_deep < $this->max_deep) {
            $childs = $this->em->getClassMetadata($entity_name)->getAssociationMappings();
            foreach ($childs as $key => $child) {
                if ($child['targetEntity'] && !in_array($child['targetEntity'], $this->join)) {
                    $this->current_deep ++;
                    $this->join[] = $child['targetEntity'];
                    $j = $this->generateAlias();
                    $table = strtolower(str_replace('Entity\\', '', $child['targetEntity']));
                    $this->alias[] = $j;
                    $qb->select($this->alias);
                    $qb->leftJoin($join_alias . '.' . $table, $j);
                    $table_child = $this->em->getClassMetadata('Entity\\' . ucfirst($table))->getAssociationMappings();
                    foreach ($table_child as $k => $p) {
                        $this->getChilds($qb, 'Entity\\' . ucfirst($table), $j);
                    }
                }
            }
        }
    }

    private function generateAlias($lenght = 10) {
        return substr(str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $lenght);
    }

    public function getWithParent($id, $entity_parent, $page = 1, $limit = 100) {

        $data = [];
        $this->children_entity_name = $this->entity_name;
        $this->entity_name = $entity_parent;
        $alias = $this->generateAlias();
        $alias_parent = $this->generateAlias();
        $qb = $this->entity->createQueryBuilder($alias)->select($alias);
        $this->join[] = $this->children_entity_name;
        $this->alias[] = $alias;
        $this->alias[] = $alias_parent;
        $qb->select(array($alias, $alias_parent));
        $qb->leftJoin($alias . '.' . strtolower($this->entity_name), $alias_parent);
        $this->getChilds($qb, $this->children_entity_name, $alias);
        $qb->where($alias_parent . '.id=' . $id);
        $query = $qb->setFirstResult($limit * ($page - 1))->setMaxResults($limit)->getQuery();
        $paginator = new Paginator($query);
        $data[strtolower(str_replace('Entity\\', '', $this->children_entity_name))] = $query->getArrayResult();
        $this->rows = $paginator->count();
        return $data;
    }

    public function get($id = null, $page = 1, $limit = 100) {
        $data = [];
        $alias = $this->generateAlias();
        $qb = $this->entity->createQueryBuilder($alias)->select($alias);
        $this->join[] = $this->entity_name;
        $this->alias[] = $alias;
        if ($id) {
            $qb->where($alias . '.id=' . $id);
        }
        $this->getChilds($qb, $this->entity_name, $alias);
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
