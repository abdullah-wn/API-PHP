<?php
namespace App\Utils;

use App\Utils\Entity\Entity;

interface DAOInterface
{
    /**
     * 
     * @param Entity[] $entities
     * @param Entity $entity
     * @param string $id
     * @param callable[] $actionsOnQuery
     * @param callable[] $actionsOnResult
     */
    public function getById(array $entities, Entity $entity, $id,
        array $actionsOnQuery = null,
        array $actionsOnResult = null);
    
    /**
     * 
     * @param Entity[] $entities
     * @param Entity $entity
     * @param array $fields
     * @param callable[] $actionsOnQuery
     * @param callable[] $actionsOnResult
     */
    public function getByFields(array $entities, Entity $entity, array $fields,
        array $actionsOnQuery = null,
        array $actionsOnResult = null);
    
    /**
     * 
     * @param Entity[] $entities
     * @param Entity $entity
     * @param string $id
     * @param array $actionsOnQuery
     * @param array $actionsOnResult
     */
    public function delete(array $entities, Entity $entity, $id,
        array $actionsOnQuery = null,
        array $actionsOnResult = null);
    
    /**
     * 
     * @param Entity[] $entities
     * @param Entity $entity
     * @param object $object
     * @param array $actionsOnQuery
     * @param array $actionsOnResult
     */
    public function update(array $entities, Entity $entity, $object,
        array $actionsOnQuery = null,
        array $actionsOnResult = null);
}

