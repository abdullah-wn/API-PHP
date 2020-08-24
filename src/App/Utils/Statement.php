<?php

namespace App\Utils;

use PDOStatement;

class Statement
{
    /**
     * Undocumented variable
     *
     * @var string $entityName
     */
    private $entityName;

    /**
     * Undocumented variable
     *
     * @var PDOStatement $statement
     */
    private $statement;

    /**
     * Undocumented variable
     *
     * @var string $action
     */
    private $action;

    /*
    public function __construct($entityName, $statement)
    {
        $this->entityName = $entityName;
        $this->statement = $statement;
    }
    */

    /**
     * Undocumented function
     *
     * @return string
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * Undocumented function
     *
     * @param string $entityName
     * @return void
     */
    public function setEntityName($entityName)
    {
        $this->entityName = $entityName;
    }

    /**
     * Undocumented function
     *
     * @return PDOStatement
     */
    public function getStatement()
    {
        return $this->statement;
    }

    /**
     * Undocumented function
     *
     * @param PDOStatement $statement
     * @return void
     */
    public function setStatement($statement)
    {
        $this->statement = $statement;
    }

    /**
     * Undocumented function
     *
     * @return int
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Undocumented function
     *
     * @param int $action
     * @return void
     */
    public function setAction($action)
    {
        $this->action = $action;
    }
}
