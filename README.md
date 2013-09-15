PDO wrapper with query builder
==========================

PDO wrapper extends PDO and PDOStatement classes and add some nice methods as insert/update/delete and so on. Also, there is very useful SQL query builder.

API
---
Because library extends [PDO driver](http://php.net/manual/en/book.pdo.php), you can use all of native PDO methods and new additional:
###DB - The database class
+ `insert` - insert object or array as row to database table (optionaly: using prepared statement)
+ `update` - update existent row in database table (optionaly: using prepared statement)
+ `replace` - insert or replace (using REPLACE table... syntax)
+ `save` - save data to table (method determinate does insert or update will be used)
+ `delete` - delete row(s) in database table
+ `count` - shortcut for SELECT COUNT(*) statement
+ `select` - query build object
+ `createQuery` - create new query builder
+ `getColumnsFromTable` - all columns from table as array

###Statement
+ `fetchInto` - fetch row into object (optionaly: only from specific table)
+ `fetchIntoFromLastRow` - fetch another object from last row (based on table name)
+ `fetchCollection` - fetch collection of objects (custom defined object or stdClass)
+ `getColumnValue` - value from specific column

###Query - Build SQL query object
+ `select` - statement for SELECT
+ `from` - statement for FROM
+ `where` - adding new WHERE statement. Multiple where will be joined by AND
+ `whereIn` - adding WHERE IN (...) statement
+ `whereNotIn` - adding WHERE NOT IN (...) statement
+ `having` - statement for HAVING
+ `join` - join table syntax
+ `groupBy` - GROUP BY statement
+ `orderBy` - ORDER BY statement
+ `limit` - LIMIT statement
+ `getQuery` - buld and return query string
+ `execute` - execute query

See more information about [how to use database query builder](https://github.com/salebab/database/wiki/How-to-use-Database-query-builder).

Usage examples
-----------------

### Creating database instance
    $db = new database\DB("mysql:host=localhost;dbname=YOUR_DB_NAME", "YOUR_DB_USERNAME", "YOUR_DB_PASSWORD");

### Select
Execute query and fetch **User** object:
	
	class User {}
	
	$user_id = 1;
	$sql = "SELECT * FROM users WHERE user_id = ? AND is_active = ?";
	$user = $db->executeQuery($sql, array($user_id, 1))
		->fetchInto(new User); // or ->fetchObject("User") as in standard PDO driver

If you need a collection of **User** objects, you can use `fetchCollection` method:

	$users = $db->executeQuery($sql, array($user_id, 1))
		->fetchCollection(new User); // or ->fetchCollection("User");


More complex, with query builder. You can build 'native' structure of objects.
For example, you can fetch collection of objects **Post** and every **Post** object may have a property `$author` which is a instance of **User** object:

	class User
	{
	    /**
	     * Get user's first and last name
	     *
	     * @return string
	     */
	    function getName() {
	        return $this->first_name . " ". $this->last_name;
	    }
	}
	
	class Post
	{
		/**
		 * @var User
		 */
		public $author;
	}

	// Library need FETCH_TABLE_NAMES option for mapping class names and table names
	$db->setFetchTableNames(1);

	$sql = $db->select("p.*, u.*")
		->from("posts p")
		->join("INNER JOIN users u USING(user_id)")
		->where("u.user_id = ?", $user_id)
		->orderBy("p.title");
		
	$stmt = $sql->execute();
	
	/* @var Post[] $post_collection  */
	$post_collection = array();

	// Fetching data into Post object from posts table (p is alias)
	while($post = $stmt->fetchInto(new Post, "p")) {

		// fetch User object from users table (u is alias)
		$post->author = $stmt->fetchIntoFromLastRow(new User, "u");

		$post_collection[] = $post;
	}

	// You can send $post_collection from model to view in your controller, so here is usage in view
	foreach($post_collection as $post) {
	    echo $post->author->getName();
	}

### Insert 
Library has `insert` method for easy inserting **array or object** as row to database table. Note that all other properties or elements that not match column names will be ignored.

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
	// some as $db->exec("DELETE FROM users WHERE user_id = 1");
	$db->delete("users", "user_id = ?", $user_id);
	
	// Count rows in table
	$count = $db->count("users");
	
	/* @var User[] $users Collection of User objects */
	$users = $db->executeQuery("SELECT * FROM users")->fetchCollection(new User);

[See more examples for Sakila database](https://github.com/salebab/database/tree/master/examples/sakila)