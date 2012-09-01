<?php

// config
if(!defined("DB_DSN")) {
    define("DB_DSN", "mysql:host=localhost;dbname=example");
    define("DB_USER", "root");
    define("DB_PASS", "");
}

include_once "DB.php";
