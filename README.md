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
	
### Insert with prepared statement

Third param for `insert()` method is "unique prepared stmt key". Every insert witch have that key will use the same prepared statement.

	foreach($data_array as $data) {
		DB::getInstance()->insert("users", $data, "unique_stmt_key");
	}

### Update

Some examples of update statement
	
	$user_id = 1;
	DB::getInstance()->update("users", $data, "user_id = ?", $user_id);
	DB::getInstance()->update("users", $data, "user_id = ? AND email = ?", array(1, "user@example.com"));
	
### Delete
	
	DB::getInstance()->delete("users", "user_id = 1");
	
	