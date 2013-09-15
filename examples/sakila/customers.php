<?php
include_once "_config.php";
include_once "../../src/database/DB.php";
include_once "../../src/database/Statement.php";
include_once "../../src/database/Query.php";

class Customer {}
class City {}
class Address {}
class Store {}

$db = new database\DB(DB_DSN, DB_USER, DB_PASS);

$db->setFetchTableNames(1);
$stmt = $db->select("c.customer_id, c.first_name, c.last_name")
    ->from("customer c")
    ->limit(5)

    //customer address
    ->select("ca.address, ca.postal_code, ca.phone")
    ->join("INNER JOIN address ca ON ca.address_id = c.address_id")

    // customer city
    ->select("city.city")
    ->join("INNER JOIN city ON city.city_id = ca.city_id")

    //store address
    ->select("s.store_id, sa.address")
    ->join("INNER JOIN store s ON s.store_id = c.store_id")
    ->join("INNER JOIN address sa ON sa.address_id = s.address_id")

    ->where("c.customer_id > ?", 120)
    ->where("c.customer_id < ?", 345)
    ->execute();

$customers = array();

while($customer = $stmt->fetchInto(new Customer, "c")) {
    $customer->address = $stmt->fetchIntoFromLastRow(new Address, "ca");
    $customer->address->city = $stmt->fetchIntoFromLastRow(new City, "city");

    $customer->store = new Store;
    $stmt->fetchIntoFromLastRow($customer->store, "s");
    $stmt->fetchIntoFromLastRow($customer->store, "sa");


    $customers[] = $customer;
}
$db->setFetchTableNames(0); // reset to default
echo "<pre>";
print_r($customers);
echo "</pre>";
