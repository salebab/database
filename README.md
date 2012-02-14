PHP/MySQL Database wrapper
==========================

MySQL datadabase wrapper for PHP extends PDO and PDOStatement classes and adds some nice functionality.

Usage
-----

### Insert 
	$data = array(
		"username" => "User 1234",
		"email" => "user@example.com",
		"mtime" => time()
	);
	DB::getInstance()->insert("users", $data);