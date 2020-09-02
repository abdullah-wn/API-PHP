<?php

namespace App\Utils;

use App\Utils\Entity\Entity;
use PDO;

class DAO implements DAOInterface
{
    const GET_BY_ID = 0;

    const GET_BY_FIELDS = 1;

    const GET_ALL = 2;

    const UPDATE = 3;

    const DELETE_BY_FIELDS = 4;

    const DELETE = 5;

    const CREATE = 6;

    private $config;

    private $client;

    private static $getByIdStatement = null;

    private static $getByFieldsStatement = null;

    private static $getAllStatement = null;

    private static $updateStatement = null;

    private static $deleteByFieldsStatement = null;

    private static $deleteStatement = null;

    private static $createStatement = null;

    public function __construct()
    {
        $pathConfig = __DIR__ . "/../../../db_config.json";
        $this->config = json_decode(file_get_contents($pathConfig));
        $this->client = SingletonDatabase::getInstance($this->config);

        self::$getByIdStatement = new Statement();
        self::$getByFieldsStatement = new Statement();
        self::$getAllStatement = new Statement();
        self::$updateStatement = new Statement();
        self::$deleteByFieldsStatement = new Statement();
        self::$deleteStatement = new Statement();
        self::$createStatement = new Statement();
    }

    /**
     *
     * @param Entity[] $entities
     * @param Entity $entity
     * @param string $id
     * @param callable[] $actionsOnQuery
     * @param callable[] $actionsOnResult
     */
    public function getById(
        $entities,
        $entity,
        $id,
        $actionsOnQuery = null,
        $actionsOnResult = null
    ) {
        $object = [];
        $columns = $entity->columns;
        $columnNames = [];
        $columnRefNames = [];
        $properties = [];
        $lists = [];
        $refs = [];

        $client = $this->client;
        if (property_exists($entity, 'inherits')) {
            $parent = $entities[$entity->inherits];
            if ($parent) {
                $columns = EntityManager::handleInherits(
                    $entities,
                    $parent,
                    $columns
                );
            }
        }

        $func = function ($column) use ($entity) {
            return "`{$entity->table}`.`{$column}`";
        };

        EntityManager::handleColumns(
            $columns,
            $columnNames,
            $properties,
            $refs,
            $columnRefNames,
            $lists
        );
        $selectColumns = join(',', array_map($func, $columnNames));

        $sql = "SELECT {$selectColumns} 
                        FROM `{$entity->table}`
                        WHERE `{$entity->id}` = :id";

        $sql = $this->handleOnQueryActions(
            self::GET_BY_ID,
            $actionsOnQuery,
            $sql,
            $id
        );

        if (
            self::$getByIdStatement->getEntityName() !== $entity ||
            self::$getByIdStatement->getAction() !== self::GET_BY_ID
        ) {
            self::$getByIdStatement->setEntityName($entity);
            self::$getByIdStatement->setStatement($client->prepare($sql));
        }

        $stmt = self::$getByIdStatement->getStatement();
        self::$getByIdStatement->getStatement()->execute([
            'id' => $id,
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $object = $this->handleOnResultActions(
            self::GET_BY_ID,
            $actionsOnResult,
            $result,
            $object
        );

        $this->handleList($object, $lists, $result, $entities);
        $this->fillObject(
            $object,
            $result,
            $columnRefNames,
            $columnNames,
            $entities,
            $refs,
            $properties,
            $columns
        );

        return $object;
    }

    /**
     *
     * @param Entity[]  $entities
     * @param Entity   $entity
     * @param []         $fields
     * @param callable[]  $actionsOnQuery
     * @param callable[] $actionsOnResult
     */
    public function getByFields(
        $entities,
        $entity,
        $fields,
        $actionsOnQuery = null,
        $actionsOnResult = null
    ) {
        $objects = [];
        $columns = $entity->columns;
        $columnNames = [];
        $columnRefNames = [];
        $properties = [];
        $lists = [];
        $refs = [];
        $columnNamesQuery = [];
        $client = $this->client;

        if (property_exists($entity, 'inherits')) {
            $parent = $entities[$entity->inherits];
            if ($parent) {
                $columns = EntityManager::handleInherits(
                    $entities,
                    $parent,
                    $columns
                );
            }
        }

        EntityManager::handleColumns(
            $columns,
            $columnNames,
            $properties,
            $refs,
            $columnRefNames,
            $lists
        );

        foreach (array_keys($fields) as $field) {
            $columnEntity = $entity->columns[$field];
            if ($columnEntity) {
                if ($columnEntity->name) {
                    $columnNamesQuery[] = $columnEntity->name;
                } else {
                    $columnNamesQuery[] = $columnEntity->reference->from;
                }
            }
        }

        $func = function ($column) use ($entity) {
            return "`{$entity->table}`.`${column}`";
        };

        $displayColumns = join(',', array_map($func, $columnNames));

        $func = function ($columnName) use ($entity) {
            return "`{$entity->table}`.`{$columnName}` = ?";
        };

        $whereFields = join(',', array_map($func, $columnNamesQuery));

        $sql = "SELECT {$displayColumns} 
                       FROM `{$entity->table}`
                       WHERE {$whereFields}";

        $sql = $this->handleOnQueryActions(
            self::GET_BY_FIELDS,
            $actionsOnQuery,
            $sql,
            $fields
        );

        if (
            self::$getByFieldsStatement->getEntityName() !== $entity ||
            self::$getByFieldsStatement->getAction() !== self::GET_BY_FIELDS
        ) {
            self::$getByFieldsStatement->setEntityName($entity);
            self::$getByFieldsStatement->setStatement($client->prepare($sql));
        }

        $stmt = self::$getByFieldsStatement->getStatement();
        $stmt->execute(array_values($fields));

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $objs = $this->handleOnResultActions(
            self::GET_BY_FIELDS,
            $actionsOnResult,
            $result,
            $objects
        );

        $objects = $objs;

        foreach ($result as $row) {
            $objects[] = [];
            $this->handleList(
                $objects[count($objects) - 1],
                $lists,
                $row,
                $entities
            );
            $this->fillObject(
                $objects[count($objects) - 1],
                $row,
                $columnRefNames,
                $columnNames,
                $entities,
                $refs,
                $properties,
                $columns
            );
        }

        return $objects;
    }

    /**
     *
     * @param Entity[]  $entities
     * @param Entity    $entity
     * @param \stdClass   $object
     * @param callable[]   $actionsOnQuery
     * @param callable[]    $actionsOnResult
     */
    public function update(
        $entities,
        $entity,
        $object,
        $actionsOnQuery = null,
        $actionsOnResult = null
    ) {
        $columns = $entity->columns;
        $columnsNotRef = [];
        $columnNames = [];
        $columnRefNames = [];
        $properties = [];
        $lists = [];
        $refs = [];
        // TODO modify to handle inherits Entity
        $idField = EntityManager::getPropertyFromColumn($entity, $entity->id);
        $queryValues = [];
        $queryFields = [];

        $object = (array) $object;
        Validator::validate($object, $entity);

        $client = $this->client;

        $idProperty = EntityManager::getPropertyFromColumn(
            $entity,
            $entity->id
        );
        if (!$idProperty) {
            throw new \Exception("fail to get id's property");
        }

        $oldData = $this->getById($entities, $entity, $object[$idProperty]);

        if (!$oldData) {
            throw new \Exception("data doesn't exist");
        }

        if (property_exists($entity, 'inherits')) {
            $parent = $entities[$entity->inherits];
            if ($parent) {
                $columns = EntityManager::handleInherits(
                    $entities,
                    $parent,
                    $columns
                );
            }
        }
        EntityManager::handleColumns(
            $columns,
            $columnNames,
            $properties,
            $refs,
            $columnRefNames,
            $lists
        );

        $listProperties = EntityManager::getListProperties($entity);

        // update and create list's elements
        foreach ($listProperties as $property) {
            if (
                $object[$property] &&
                is_array($object[$property]) &&
                count($object[$property])
            ) {
                $listEntity = $entities[$object[$property][0]->_type];
                if ($listEntity) {
                    $listIdField = EntityManager::getPropertyFromColumn(
                        $entity,
                        $entity->id
                    );
                    if (!$listIdField) {
                        throw new \Exception(
                            "fail to get id's property of ${$object[$property][0]->_type}'s list"
                        );
                    }
                    $func = function ($item) use (
                        $object,
                        $property,
                        $listIdField
                    ) {
                        foreach ($object[$property] as $item2) {
                            if (
                                property_exists($item2, $listIdField) &&
                                $item[$listIdField] === $item2->{$listIdField}
                            ) {
                                return false;
                            }
                        }
                        return true;
                    };

                    $rms = array_filter($oldData[$property], $func);

                    foreach ($rms as $rm) {
                        $this->delete(
                            $entities,
                            $listEntity,
                            $rm[$listIdField]
                        );
                    }

                    $i = 0;
                    $ref = EntityManager::getPropertyFromEntityName(
                        $listEntity,
                        $entity->name
                    );
                    foreach ($object[$property] as $item) {
                        if (property_exists($item, $listIdField)) {
                            $object[$property][$i] = $this->update(
                                $entities,
                                $listEntity,
                                $item
                            );
                        } else {
                            $new = (array) $item;
                            $new[$ref] = $object;
                            $object[$property][$i] = $this->create(
                                $entities,
                                $listEntity,
                                $new
                            );
                        }
                        
                        unset($object[$property][$i]->{$ref});
                        $i++;
                    }
                }
            }
        }

        $columnsNotRef = EntityManager::getColumnsNotRefWithOnlyPropertyNames(
            $entity
        );

        foreach ($columnsNotRef as $key => $columnName) {
            if ($columnName !== $entity->id && $object[$key]) {
                $queryFields[] = $columnName;
                if (gettype($object[$key]) === 'object') {
                    $queryValues[] = json_encode($object[$key]);
                } else {
                    $queryValues[] = $object[$key];
                }
            }
        }

        $columns = EntityManager::getColumnsRefWithProperties($entity);

        foreach ($columns as $key => $column) {
            if ($object[$key]) {
                $refEntity = $entities[$column->entity];

                if (!$refEntity) {
                    throw new \Exception('Unexpected error');
                }
                if ($refEntity->allowUpdateFromRef) {
                    $this->update($entities, $refEntity, $object[$key]);
                }
            }
        }

        // Build the string which update fields in $sql Query "Column1 = $2, Column2 = $3, Column3 = $4..."
        $func = function ($field) {
            return "{$field} = ?";
        };

        $updateFields = join(',', array_map($func, $queryFields));

        $sql = "UPDATE `{$entity->table}` SET {$updateFields} WHERE `{$entity->table}`.`{$entity->id}` = ?";

        $queryValues[$idField] = $object[$idField];
        $sql = $this->handleOnQueryActions(
            self::UPDATE,
            $actionsOnQuery,
            $sql,
            $queryValues
        );

        if (
            self::$updateStatement->getEntityName() !== $entity ||
            self::$updateStatement->getAction() !== self::UPDATE
        ) {
            self::$updateStatement->setEntityName($entity);
            self::$updateStatement->setStatement($client->prepare($sql));
        }

        $stmt = self::$updateStatement->getStatement();

        $result = $stmt->execute(array_values($queryValues));

        $object = (object) array_merge(
            (array) $object,
            (array) $this->handleOnResultActions(
                self::UPDATE,
                $actionsOnResult,
                $result,
                $object
            )
        );

        return $object;
    }

    /**
     *
     * @param Entity[] $entities
     * @param Entity $entity
     * @param \stdClass $object
     * @param callable[] $actionsOnQuery
     * @param callable[] $actionsOnResult
     */
    public function create(
        $entities,
        $entity,
        $object,
        $actionsOnQuery = null,
        $actionsOnResult = null
    ) {
        $columns = $entity->columns;
        $columnNames = [];
        $columnRefNames = [];
        $properties = [];
        $lists = [];
        $refs = [];
        $queryColumns = [];
        $queryValues = [];

        $object = (array) $object;

        Validator::validate($object, $entity);

        $client = $this->client;
        if (property_exists($entity, 'inherits')) {
            $parent = $entities[$entity->inherits];
            if ($parent) {
                $columns = EntityManager::handleInherits(
                    $entities,
                    $parent,
                    $columns
                );
            }
        }
        EntityManager::handleColumns(
            $columns,
            $columnNames,
            $properties,
            $refs,
            $columnRefNames,
            $lists
        );

        $columnsNotRef = EntityManager::getColumnsNotRefWithOnlyPropertyNames(
            $entity
        );

        foreach ($columnsNotRef as $key => $columnName) {
            if ($columnName !== $entity->id && $object[$key]) {
                $queryColumns[] = "`{$columnName}`";
                if (gettype($object[$key]) === 'object') {
                    $queryValues[$key] = json_encode($object[$key]);
                } else {
                    $queryValues[$key] = $object[$key];
                }
            }
        }

        $columnsRef = EntityManager::getColumnsRefWithProperties($entity);

        foreach ($columnsRef as $key => $column) {
            $entityRef = $entities[$column->entity];
            if ($entityRef) {
                $property = EntityManager::getPropertyFromColumn(
                    $entityRef,
                    $column->reference->to
                );
                if ($property && ($object[$key][$property] || $object[$key])) {
                    $queryColumns[] = "`{$column->reference->from}`";
                }

                if ($property) {
                    if ($object[$key][$property]) {
                        $queryValues[$key] = $object[$key][$property];
                    } elseif ($object[$key]) {
                        $queryValues[$property] = $object[$property];
                    }
                }
            }
        }

        $fields = join(',', $queryColumns);
        $insertFields = "`{$entity->table}`({$fields})";
        $func = function () {
            return '?';
        };
        $insertValues = join(',', array_map($func, $queryColumns));

        $sql = "INSERT INTO {$insertFields} VALUES ({$insertValues})";

        $sql = $this->handleOnQueryActions(
            self::CREATE,
            $actionsOnQuery,
            $sql,
            $queryValues
        );

        if (
            self::$createStatement->getEntityName() !== $entity ||
            self::$createStatement->getAction() !== self::CREATE
        ) {
            self::$createStatement->setEntityName($entity);
            self::$createStatement->setStatement($client->prepare($sql));
        }

        $stmt = self::$createStatement->getStatement();
        $result = $stmt->execute(array_values($queryValues));

        if (
            $idProp = EntityManager::getPropertyFromColumn($entity, $entity->id)
        ) {
            $object[$idProp] = $client->lastInsertId();
        }

        $listProperties = EntityManager::getListProperties($entity);

        // create list's elements
        foreach ($listProperties as $property) {
            if (
                $object[$property] &&
                is_array($object[$property]) &&
                count($object[$property])
            ) {
                $listEntity = $entities[$object[$property][0]->_type];
                $column = $columns[$property];
                if ($listEntity && $column) {
                    $i = 0;
                    $propertyRef = EntityManager::getPropertyFromColumn(
                        $listEntity,
                        $column->reference->to
                    );

                    foreach ($object[$property] as $item) {
                        if ($propertyRef && $idProp) {
                            $item[$propertyRef] = $object[$idProp];
                        }
                        $object[$property][$i] = $this->create(
                            $entities,
                            $listEntity,
                            $item
                        );
                        $i++;
                    }
                }
            }
        }
        $object = (object) array_merge(
            (array) $object,
            (array) $this->handleOnResultActions(
                self::CREATE,
                $actionsOnResult,
                $result,
                $object
            )
        );

        return $object;
    }

    /**
     *
     * @param Entity[] $entities
     * @param Entity $entity
     * @param int | string $id
     * @param callable[] $actionsOnQuery
     * @param callable[] $actionsOnResult
     */
    public function delete(
        $entities,
        $entity,
        $id,
        $actionsOnQuery = null,
        $actionsOnResult = null
    ) {
        $client = $this->client;
        $queryValues = [
            'id' => $id,
        ];

        $sql = "DELETE FROM `{$entity->table}` WHERE `{$entity->table}`.`{$entity->id}` = ?";

        if ($actionsOnQuery) {
            $sql = $this->handleOnQueryActions(
                self::DELETE,
                $actionsOnQuery,
                $sql,
                $queryValues
            );
        }

        if (
            self::$deleteStatement->getEntityName() !== $entity ||
            self::$deleteStatement->getAction() !== self::DELETE
        ) {
            self::$deleteStatement->setEntityName($entity);
            self::$deleteStatement->setStatement($client->prepare($sql));
        }

        $stmt = self::$deleteStatement->getStatement();
        $result = $stmt->execute(array_values($queryValues));

        if ($actionsOnResult) {
            $this->handleOnResultActions(
                self::DELETE,
                $actionsOnResult,
                $result,
                $id
            );
        }

        return $result;
    }

    /**
     *
     * @param Entity[] $entities
     * @param Entity $entity
     * @param [] $fields
     * @param callable[] $actionsOnQuery
     * @param callable[] $actionsOnResult
     */
    public function deleteByFields(
        $entities,
        $entity,
        $fields,
        $actionsOnQuery = null,
        $actionsOnResult = null
    ) {
        $columnNamesQuery = [];
        $client = $this->client;

        foreach (array_keys($fields) as $field) {
            $columnEntity = $entity->columns[$field];
            if ($columnEntity) {
                if ($columnEntity->name) {
                    $columnNamesQuery[] = $columnEntity->name;
                } else {
                    $columnNamesQuery[] = $columnEntity->reference->from;
                }
            }
        }

        $func = function ($columnName) use ($entity) {
            return "`{$entity->table}`.`{$columnName}` = ?";
        };

        $whereFields = join(',', array_map($func, $columnNamesQuery));

        $sql = "DELETE FROM `{$entity->table}` WHERE {$whereFields}";

        if ($actionsOnQuery) {
            $sql = $this->handleOnQueryActions(
                self::DELETE_BY_FIELDS,
                $actionsOnQuery,
                $sql,
                $fields
            );
        }

        if (
            self::$deleteByFieldsStatement->getEntityName() !== $entity ||
            self::$deleteByFieldsStatement->getAction() !==
                self::DELETE_BY_FIELDS
        ) {
            self::$deleteByFieldsStatement->setEntityName($entity);
            self::$deleteByFieldsStatement->setStatement(
                $client->prepare($sql)
            );
        }

        $stmt = self::$deleteByFieldsStatement->getStatement();
        $result = $stmt->execute(array_values($fields));

        if ($actionsOnResult) {
            $this->handleOnResultActions(
                self::DELETE_BY_FIELDS,
                $actionsOnResult,
                $result,
                $fields
            );
        }

        return $result;
    }

    /**
     *
     * @param Entity[] $entities
     * @param Entity $entity
     * @param callable[] $actionsOnQuery
     * @param callable[] $actionsOnResult
     */
    public function getAll(
        $entities,
        $entity,
        $actionsOnQuery = null,
        $actionsOnResult = null
    ) {
        $columns = $entity->columns;
        $columnNames = [];
        $columnRefNames = [];
        $properties = [];
        $lists = [];
        $refs = [];

        $client = $this->client;

        if (property_exists($entity, 'inherits')) {
            $parent = $entities[$entity->inherits];
            if ($parent) {
                $columns = EntityManager::handleInherits(
                    $entities,
                    $parent,
                    $columns
                );
            }
        }

        EntityManager::handleColumns(
            $columns,
            $columnNames,
            $properties,
            $refs,
            $columnRefNames,
            $lists
        );

        $func = function ($column) use ($entity) {
            return "`{$entity->table}`.`{$column}`";
        };

        $selectFields = join(',', array_map($func, $columnNames));

        $sql = "SELECT {$selectFields} FROM `{$entity->table}`";

        $sql = $this->handleOnQueryActions(
            self::GET_ALL,
            $actionsOnQuery,
            $sql,
            null
        );

        if (
            self::$getAllStatement->getEntityName() !== $entity ||
            self::$getAllStatement->getAction() !== self::GET_ALL
        ) {
            self::$getAllStatement->setEntityName($entity);
            self::$getAllStatement->setStatement($client->prepare($sql));
        }

        $stmt = self::$getAllStatement->getStatement();
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $func = function ($row) use (
            $lists,
            $entities,
            $columnRefNames,
            $columnNames,
            $refs,
            $properties,
            $columns
        ) {
            $object = [];

            $this->handleList($object, $lists, $row, $entities);

            $this->fillObject(
                $object,
                $row,
                $columnRefNames,
                $columnNames,
                $entities,
                $refs,
                $properties,
                $columns
            );

            return $object;
        };

        // $closure = \Closure::bind($func, $this, 'DAO');

        $objects = array_map($func, $rows);

        $objects = $this->handleOnResultActions(
            self::GET_ALL,
            $actionsOnResult,
            $rows,
            $objects
        );

        return $objects;
    }

    public function handleList(&$object, $lists, $row, $entities)
    {
        $listValues = [];
        if (count($lists) > 0) {
            foreach ($lists as $list) {
                $listValues[] = $row[$list->reference->from];
            }
            $objectsList = $this->retrieveList($entities, $lists, $listValues);

            foreach ($objectsList as $key => $obj) {
                $object[$key] = $obj;
            }
        }
    }

    public function fillObject(
        &$object,
        $row,
        $columnRefNames,
        $columnNames,
        $entities,
        $refs,
        $properties,
        $columns
    ) {
        if (count($refs) > 0) {
            $refValues = array_map(function ($columnName) use ($row) {
                return $row[$columnName];
            }, $columnRefNames);
            $objectsRefs = $this->retrieveRefs($entities, $refs, $refValues);
        }

        for ($i = 0; $i < count($columnNames); $i++) {
            if (in_array($columnNames[$i], $columnRefNames)) {
                if ($objectsRefs) {
                    $object[$properties[$i]] = $objectsRefs[$properties[$i]];
                }
            } else {
                $value = $row[$columnNames[$i]];
                $column = $columns[$properties[$i]];
                if ($column) {
                    $object[$properties[$i]] =
                        $column->entity === 'JSON'
                            ? json_encode($value)
                            : $value;
                }
            }
        }
    }

    public static function buildQueryForRef($entity, $columnRef)
    {
        $columnsNames = EntityManager::getColumnsNotRefOnlyName($entity);

        $func = function ($columnName) use ($entity) {
            return "`{$entity->table}`.`{$columnName}`";
        };

        $selectFields = join(',', array_map($func, $columnsNames));
        return "SELECT {$selectFields} FROM `{$entity->table}` WHERE `${columnRef}` = ?";
    }

    /**
     *
     * @param Entity[] $entities
     * @param [] $refs
     * @param [] $refValues
     * @return \stdClass
     */
    public function retrieveRefs($entities, $refs, $refValues)
    {
        $objects = [];
        $i = 0;
        $client = $this->client;

        foreach ($refs as $keyRef => $ref) {
            $entity = $entities[$ref->entity];
            if ($entity) {
                $columns = EntityManager::getColumnsNotRefWithOnlyPropertyNames(
                    $entity
                );

                $query = self::buildQueryForRef($entity, $ref->reference->to);
                $stmt = $client->prepare($query);

                $stmt->execute([$refValues[$i]]);

                if ($res = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $object = [];
                    foreach ($columns as $key => $columnName) {
                        $value = $res[$columnName];
                        $column = $entity->columns[$key];
                        if ($column) {
                            $object[$key] =
                                $column->entity === 'JSON'
                                    ? json_encode($value)
                                    : $value;
                        }
                    }

                    if ($ref->full) {
                        $refsColumns = EntityManager::getColumnsRefWithProperties(
                            $entity
                        );
                        $refValues2 = [];
                        foreach ($refsColumns as $column) {
                            $property = EntityManager::getPropertyFromColumn(
                                $entity,
                                $column->reference->from
                            );
                            if ($property) {
                                $refValues2[] = $object[$property];
                            }
                        }

                        foreach (
                            $this->retrieveRefs(
                                $entities,
                                $refsColumns,
                                $refValues2
                            )
                            as $field => $value
                        ) {
                            $object[$field] = $value;
                        }
                    }
                    $object['_type'] = $ref->entity;
                }
                $objects[$keyRef] = $object;
            }
            $i++;
        }

        return $objects;
    }

    public function retrieveList($entities, $lists, $listValues)
    {
        $objectList = [];
        $i = 0;

        foreach ($lists as $key => $list) {
            $result = [];
            foreach ($list->entities as $entityName) {
                $e = $entities[$entityName];
                if ($e) {
                    $entity = clone $e;

                    //$entity->columns = $e->columns;
                    /*
                     * if (property_exists($entity, 'inherits')) {
                     * $parent = $entities[$entity->inherits];
                     * if ($parent)
                     * entity.$columns = EntityManager::handleInherits($entities, $parent, $columns);
                     * }
                     */

                    if (property_exists($entity, 'inherits')) {
                        $entity->columns = EntityManager::handleInherits(
                            $entities,
                            $entity,
                            $entity->columns
                        );
                        $entity->inherits;
                    }

                    $propertyRef = unserialize(
                        serialize(
                            EntityManager::getFullPropertyRefFromColumn(
                                $entity,
                                $list->reference->to
                            )
                        )
                    );

                    if (!$list->full) {
                        $entity->columns = EntityManager::getColumnsNotRefWithProperties(
                            $entity
                        );
                    }

                    $propertyRef->column->name =
                        $propertyRef->column->reference->from;
                    $propertyRef->column->reference = null;
                    $propertyRef->column->entity = null;

                    $entity->columns[$propertyRef->property] =
                        $propertyRef->column;

                    $object = $this->getByFields($entities, $entity, [
                        $propertyRef->property => $listValues[$i],
                    ]);
                    if ($object) {
                        for ($j = 0; $j < count($object); $j++) {
                            $object[$j]['_type'] = $entityName;
                        }
                        $result = array_merge($result, $object);
                    }
                }
            }

            $objectList[$key] = $result;
            $i++;
        }
        return $objectList;
    }

    private function handleOnQueryActions(
        $actionType,
        $actionsOnQuery,
        $sql,
        $object
    ) {
        if ($actionsOnQuery) {
            foreach ($actionsOnQuery as $action) {
                $sql = $action($actionType, $sql, $object);
            }
        }

        return $sql;
    }

    private function handleOnResultActions(
        $actionType,
        $actionsOnResult,
        $result,
        $response
    ) {
        if ($actionsOnResult) {
            foreach ($actionsOnResult as $action) {
                $response = $action($actionType, $result, $response);
            }
        }

        return $response;
    }
}
