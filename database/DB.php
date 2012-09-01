<?php
include_once "DBWrapper.php";

class DB extends DBWrapper
{
    /**
     * @var DB
     */
    private static $instance;

    /**
     * @return DB
     */
    static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self(DB_DSN, DB_USER, DB_PASS);
        }

        return self::$instance;
    }
}