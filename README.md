PHP/MySQL Database wrapper
==========================

MySQL datadabase wrapper for PHP extends PDO and PDOStatement classes and adds some nice functionality.

Usage examples
-----------------

### Creating database instance
    require "database.php";
    $db = new database\DB("mysql:host=localhost;dbname=YOUR_DB_NAME", "YOUR_DB_USERNAME", "YOUR_DB_PASSWORD");

If you're using your own autoloader, you don't need to require database.php script.

### Select
Execute query and fetch User objects
	
	class User {}
	
	$user_id = 1;
	$sql = "SELECT * FROM users WHERE user_id = ? AND is_active = ?";
	$user = $db->executeQuery($sql, array($user_id, 1))
		->fetchInto(new User);
	
More complex, with query builder. You can build 'native' structure of objects.
For example, you can fetch collection of object Post and every Post object may have a property $author which is a instance of User object

	class User {

	    function getName() {
	        return $this->first_name . " ". $this->last_name;
	    }
	}
	
	class Post extends stdClass {
		
		/**
		 * @var User
		 */
		public $author;
	}

	DB::getInstance()->setFetchTableNames(1);
	$sql = $db->select("p.*, u.*")
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

	// Usage
	foreach($post_collection as $post) {
	    echo $post->author->getName();
	}

### Insert 

	$data = array(
		"username" => "User 1234",
		"email" => "user@example.com",
		"mtime" => time()
	);
	$db->insert("users", $data);
	
### Insert with prepared statement
Third param for `insert()` method is "unique prepared stmt key". Every insert which have that key will use the same prepared statement.

	foreach($data_array as $data) {
		$db->insert("users", $data, "unique_stmt_key");
	}

### Update
Some examples of update statement
	
	$user_id = 1;
	$db->update("users", $data, "user_id = ?", $user_id);
	$db->update("users", $data, "user_id = ? AND email = ?", array(1, "user@example.com"));
	
	
### Saving data
Automatic determination of INSERT or UPDATE. If $data['user_id'] exits it will be UPDATE, otherwise it will be INSERT.

	$db->save("users", $data, "user_id"); // user_id is name of PRIMARY column
	
### More examples
	
	// Delete row in table
	// some as DB::getInstance()->exec("DELETE FROM users WHERE user_id = 1");
	$db->delete("users", "user_id = ?", $user_id);
	
	// Count rows in table
	$count = $db->count("users");
	
	/* @var User[] $users Collection of User objects */
	$users = $db->executeQuery("SELECT * FROM users")->fetchCollection(new User);

[See more examples for Sakila database](https://github.com/salebab/database/tree/master/sakila-examples)