<?php
namespace App\Utils;

use PDO;

class SingletonDatabase
{
    /**
     *
     * @var PDO
     */
    private static $instance = null;

    private function __construct($config)
    {
        self::$instance = new PDO(
            "mysql:dbname={$config->db};host={$config->host};port={$config->port};charset=UTF8",
            $config->user,
            $config->password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    /**
     *
     * @param string[] $config
     * @return PDO
     */
    public static function getInstance($config)
    {
        if (!self::$instance) {
            new self($config);
        }
        return self::$instance;
    }
}
