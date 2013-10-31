<?php
include "src/database/Statement.php";
include "src/database/DB.php";

\database\DB::setConfig(array(
        "dsn" => "mysql:host=localhost;dbname=mobjizz",
        "username" => "root",
        "password" => "root"
    ));

$db = \database\DB::getInstance("set");