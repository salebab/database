PHP/MySQL Database wrapper
==========================

MySQL datadabase wrapper for PHP extends PDO and PDOStatement classes and adds some nice functionality.

Usage examples
-----------------

### Select
Execute query and fetch User objects
	
	class User extends stdClass {}
	
	$user_id = 1;
	$sql = "SELECT * FROM users WHERE user_id = ? AND is_active = ?";
	$user = DB::getInstance()
		->executeQuery($sql, array($user_id, 1))
		->fetchInto(new User);
	
More complex, with query builder

	class User extends stdClass{}
	
	class Post extends stdClass {
		
		/**
		 * @var User
		 */
		public $author;
	}
	
	$sql = DB::getInstance()
		->select("p.*, u.*")
		->from("posts p")
		->join("INNER JOIN users u USING(user_id)")
		->where("u.user_id = ?", $user_id)
		->orderBy("p.title");
		
	$stmt = $sql->execute();
	
	/* @var Post[] $post_collection  */
	$post_collection = array();
	
	while($post = $stmt->fetchInto(new Post, "p")) {
		$post->author = $stmt->fetchIntoFromLastRow(new User, "u");
		$post_collection[] = $post;
	}
	

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
	
	
### Saving data
Automatic determination of INSERT or UPDATE. If $data['user_id'] exits it will be UPDATE, otherwise it will be INSERT.

	DB::getInstance()->save("users", $data, "user_id"); // user_id is name of PRIMARY column
	
### More examples
	
	// Delete row in table
	// some as DB::getInstance()->exec("DELETE FROM users WHERE user_id = 1");
	DB::getInstance()->delete("users", "user_id = ?", $user_id);
	
	// Count rows in table
	$count = DB::getInstance()->count("users");
	
	/* @var User[] $users Collection of User objects */
	$users = DB::getInstance()
		->executeQuery("SELECT * FROM users")
		->fetchCollection(new User);

[See more examples for Sakila database](https://github.com/salebab/database/tree/master/sakila-examples)