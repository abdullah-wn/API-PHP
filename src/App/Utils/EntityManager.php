<?php

namespace App\Utils;

use App\Utils\Entity\Entity;
use Exception;

class EntityManager
{
    public static function getEntities($pathEntity, $pathModel, $defaultConfig)
    {
        $defaultConfig->columns = self::object_to_array(
            $defaultConfig->columns
        );
        $entityList = array_diff(scandir($pathEntity), ['..', '.']);
        $entities = [];

        foreach ($entityList as $entity) {
            $file = file_get_contents("$pathEntity/$entity");
            $entityName = (new RegExp('^(\w+)\.'))->exec($entity)[1];
            $entities[$entityName] = json_decode($file);
            $entities[$entityName]->columns =
                (array) $entities[$entityName]->columns;
        }
        return EntityManager::formatEntities($entities, $defaultConfig);
    }

    private static function object_to_array($obj)
    {
        //only process if it's an object or array being passed to the function
        if (is_object($obj) || is_array($obj)) {
            $ret = (array) $obj;
            foreach ($ret as &$item) {
                //recursively process EACH element regardless of type
                $item = self::object_to_array($item);
            }
            return $ret;
        }
        //otherwise (i.e. for scalar values) return without modification
        else {
            return $obj;
        }
    }

    public static function formatEntities($entities, $defaultConfig)
    {
        $fullEntities = [];

        foreach ($entities as $key => $entity) {
            $result = EntityManager::formatEntity($entity, $defaultConfig);
            $result->name = $key;
            if ($result) {
                $fullEntities[$key] = $result;
            }
        }

        return $fullEntities;
    }

    /**
     * Add all implicit fields to PartialEntity and return it as Entity
     * @param entity
     * @param defaultConfig
     */
    public static function formatEntity($entity, $defaultConfig)
    {
        $result = new Entity();

        if (!isset($entity->id)) {
            $entity->id = $defaultConfig->id;
        }

        if (property_exists($entity, 'softDelete')) {
            switch (gettype($entity->softDelete)) {
                case 'boolean':
                    $result->softDelete->on = $entity->softDelete;
                    $result->softDelete->columnName =
                        $defaultConfig->softDelete->columnName;
                    break;
                case 'object':
                    $errors = [];
                    foreach (array_keys($entity->softDelete) as $field) {
                        if (!$entity->softDelete[$field]) {
                            $errors[] = new Exception(
                                "Missing \"{$field}\" property on softDelete"
                            );
                        }
                    }
                    if (count($errors)) {
                        throw $errors;
                    }
                    break;
                default:
                    $entity->softDelete = $defaultConfig->softDelete;
            }
        } else {
            $entity = (array) $entity;
            $entity['softDelete'] = $defaultConfig->softDelete;
            $entity = (object) $entity;
        }

        if (property_exists($entity, 'columns')) {
            foreach (array_keys($entity->columns) as $field) {
                $columnEntity = $entity->columns[$field];
                switch (gettype($columnEntity)) {
                    case 'string':
                        $column = (object) $defaultConfig->columns['property'];
                        $column->name = $columnEntity;
                        $entity->columns[$field] = $column;
                        break;
                    case 'object':
                        foreach (
                            array_keys($defaultConfig->columns['property'])
                            as $fieldProperty
                        ) {
                            if (
                                !array_key_exists($fieldProperty, $columnEntity)
                            ) {
                                $columnEntity->{$fieldProperty} =
                                    $defaultConfig->columns['property'][
                                        $fieldProperty
                                    ];
                            }
                        }
                        break;
                }
            }
        }

        $finalEntity = new Entity();
        $finalEntity->columns = $entity->columns;
        $finalEntity->eventListeners[API::ON_REQUEST] = [];
        $finalEntity->eventListeners[API::ON_QUERY] = [];
        $finalEntity->eventListeners[API::ON_RESULT] = [];
        $finalEntity->eventListeners[API::ON_RESPONSE] = [];

        $finalEntity->softDelete = $entity->softDelete;
        $finalEntity->id = $entity->id;
        $finalEntity->table = $entity->table;

        return $finalEntity;
    }

    public static function getColumnsNotRefWithOnlyPropertyNames($entity)
    {
        $columns = [];

        foreach ($entity->columns as $key => $column) {
            if ($column->name && !$column->reference && !$column->list) {
                $columns[$key] = $column->name;
            }
        }

        return $columns;
    }

    public static function getColumnsNotRefWithProperties($entity)
    {
        $columns = [];

        foreach ($entity->columns as $key => $column) {
            if ($column->name && !$column->reference && !$column->list) {
                $columns[$key] = $column;
            }
        }

        return $columns;
    }

    public static function getPropertyFromColumn($entity, $columnName)
    {
        foreach ($entity->columns as $key => $column) {
            if (
                $column->name === $columnName ||
                (isset($column->reference) &&
                    $column->reference->from === $columnName) ||
                (isset($column->reference) &&
                    $column->reference->to === $columnName)
            ) {
                return $key;
            }
        }

        return null;
    }

    public static function getPropertyFromEntityName($entity, $entityName)
    {
        foreach ($entity->columns as $key => $column) {
            if ($column->entity === $entityName) {
                return $key;
            }
        }

        return null;
    }

    public static function getFullPropertyRefFromColumn($entity, $columnName)
    {
        foreach ($entity->columns as $key => $column) {
            if (
                $column->reference &&
                $column->reference->from === $columnName
            ) {
                return (object) [
                    'property' => $key,
                    'column' => $column,
                ];
            }
        }

        return null;
    }

    public static function getColumnsRefWithProperties($entity)
    {
        $columns = [];

        foreach ($entity->columns as $key => $column) {
            if (!$column->name && $column->reference && !$column->list) {
                $columns[$key] = $column;
            }
        }

        return $columns;
    }

    public static function getListsWithProperties($entity)
    {
        $columns = [];

        foreach ($entity->columns as $key => $column) {
            if ($column->list) {
                $columns[$key] = $column;
            }
        }

        return $columns;
    }

    public static function getListProperties($entity)
    {
        $columns = [];

        foreach ($entity->columns as $key => $column) {
            if ($column->list) {
                $columns[] = $key;
            }
        }

        return $columns;
    }

    public static function getColumnsNotRefOnlyName($entity)
    {
        $columnsNames = [];

        foreach ($entity->columns as $column) {
            if ($column->name && !$column->reference && !$column->list) {
                $columnsNames[] = $column->name;
            }
        }

        return $columnsNames;
    }

    public static function getColumnsNotRef($entity)
    {
        $columnsNotRef = [];

        foreach ($entity->columns as $column) {
            if ($column->name && !$column->reference && !$column->list) {
                $columnsNotRef[] = $column;
            }
        }

        return $columnsNotRef;
    }

    /*
        static function browse(parent, groups, parents) {
            $finalEntities = [];
            for ($i = 0; i < parent.children.length; i++) {
                if (typeof parent.children[i] === "object" && parent.children[i].hasOwnProperty("parent")) {
                    parents.push(parent.entity);
                    EntityManager.browse(parent.children[i].parent, groups, parents);
                } else {
                    finalEntities.push(parent.children[i]);
                }
            }

            if (finalEntities.length > 0) {
                groups.push({
                    finalEntities,
                    parents: parents.map((parent) => parent)
                });
            }
        }
    */

    /**
     * Extract columnNames, properties, columnRefNames and lists from Entity's columns
     * @param columns
     * @param columnNames
     * @param properties
     * @param refs
     * @param columnRefNames
     * @param lists
     */
    public static function handleColumns(
        &$columns,
        &$columnNames,
        &$properties,
        &$refs,
        &$columnRefNames,
        &$lists
    ) {
        foreach ($columns as $key => $column) {
            if (!$column->list) {
                if ($column->name) {
                    $columnNames[] = $column->name;
                } else {
                    $columnNames[] = $column->reference->from;
                }
                $properties[] = $key;

                if ($column->reference) {
                    $refs[$key] = $column;
                    $columnRefNames[] = $column->reference->from;
                }
            } else {
                $lists[$key] = $column;
            }
        }
    }

    /**
     * Merge columns from parent entities and the final entity
     * @param entities
     * @param entity
     * @param columns
     */
    public static function handleInherits($entities, $entity, $columns)
    {
        $result = array_merge($entity->columns, $columns);
        // new Map<string, Column>([...entity.columns, ...columns]);

        if ($entity->inherits) {
            $parent = $entities[$entity->inherits];
            if ($parent) {
                $result = EntityManager::handleInherits(
                    $entities,
                    $parent,
                    $result
                );
            }
        }

        return $result;
    }
}
