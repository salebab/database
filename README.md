PHP/MySQL Database wrapper
==========================

MySQL datadabase wrapper for PHP extends PDO and PDOStatement classes and adds some nice functionality.

Examples of usage
-----------------

### Select
Execute query and fetch User objects
	
	class User exteds DBObject {}
	
	$user_id = 1;
	$sql = "SELECT * FROM users WHERE user_id = ?";
	$user = DB::getInstance()
		->executeQuery($sql, $user_id)
		->fetchInto(new User);
	
More complexed, with query builder

	class User extends DBObject{}
	
	class Post extends DBObject {
		
		/**
		 * @var User
		 */
		public $user;
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
	
### More examples
	
	DB::getInstance()->delete("users", "user_id = 1"); // delete object
	DB::getInstance()->count("users"); // count table
	DB::getInstance()->executeQuery("SELECT * FROM users")->fetchCollection(new User); // collection of User objects (User[])
	
	