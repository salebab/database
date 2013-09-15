<?php
include_once "_config.php";
include_once "../../src/database/DB.php";
include_once "../../src/database/Statement.php";
include_once "../../src/database/Query.php";

$db = new database\DB(DB_DSN, DB_USER, DB_PASS);

class Film {
    public $film_id;
    public $title;
}

$category_name = "Action";

$stmt = $db->select("f.film_id, f.title")
    ->from("film f")
    ->join("INNER JOIN film_category fc ON fc.film_id = f.film_id")
    ->join("INNER JOIN category c ON c.category_id = fc.category_id")
    ->where("c.name = ?", $category_name)
    ->limit(5)
    ->execute();

while($film = $stmt->fetchInto(new Film)) {
    var_dump($film);
}
