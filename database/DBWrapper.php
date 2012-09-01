<?php
include_once "DBStatement.php";

/**
 * DB - MySQL database library
 *
 * @author Sasa
 *
 */
class DBWrapper extends PDO
{

    const FETCH_FROM_NEXT_ROW = 0;
    const FETCH_FROM_LAST_ROW = 1;

    /**
     * Previous prepared statements
     * @var DBStatement[]
     */
    private $prev_stmt = array();

    /**
     * Previous column used
     * ONLY if #$prev_stmt is used
     *
     * @var array
     */
    private $prev_columns = array();

    /**
     * Check if columns exists - on/off
     *
     * @var bool
     */
    private $check_columns = false;


    public $fetch_table_names = 0;

    /**
     * @param string $dsn
     * @param null|string $username
     * @param null|string $password
     * @param array $options
     */
    function __construct($dsn, $username = null, $password = null, $options = array())
    {

        $options = $options + array(
            PDO::ATTR_STATEMENT_CLASS => array("DBStatement", array($this)),
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES UTF8",
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        );

        try {
            parent::__construct($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            exit('Database connection error');
        }
    }

    /**
     * Check if columns exists for INSERT or UPDATE
     * To turn ON use checkColumns(true)
     * To turn OFF use checkColumns(false)
     *
     * @param bool|int $set
     * @return bool
     */
    function checkColumns($set = null)
    {
        if ($set !== null) {
            $this->check_columns = $set;
        }

        return $this->check_columns;
    }

    /**
     * Remove non-existed columns before insert/update
     *
     * @param string $table
     * @param array $data
     * @param mixed $prev_column_id
     * @return array
     */
    private function removeNonExistentColumns($table, &$data, $prev_column_id = null)
    {
        if ($this->check_columns) {

            // use previous columns or get new
            if (!empty($prev_column_id) && empty($this->prev_columns[$prev_column_id])) {
                $this->prev_columns[$prev_column_id] = $this->getColumnsFromTable($table);
                $columns = $this->prev_columns[$prev_column_id];
            } elseif (!empty($prev_column_id) && !empty($this->prev_columns[$prev_column_id])) {
                $columns = $this->prev_columns[$prev_column_id];
            }
            else {
                $columns = $this->getColumnsFromTable($table);
            }

            $new_data = array();
            foreach ($columns as $column) {
                if (array_key_exists($column, $data)) {
                    $new_data[$column] = $data[$column];
                }
            }
            $data = $new_data;
        }
        return $data;
    }

    /**
     * Statement for INSERT
     *
     * @param string $table
     * @param array $data
     * @return PDOStatement
     */
    private function getInsertStmt($table, $data)
    {
        $columns = $question_marks = array();

        foreach (array_keys($data) as $column) {
            $columns[] = "`" . $column . "`";
            $question_marks[] = "?";
        }

        $columns = implode(", ", $columns);
        $question_marks = implode(", ", $question_marks);

        $sql = "INSERT INTO `$table` ($columns) VALUES ($question_marks)";

        return $this->prepare($sql);
    }

    /**
     * Statement for UPDATE
     *
     * @param string $table
     * @param array $data
     * @param mixed $where
     * @return PDOStatement
     */
    private function getUpdateStmt($table, $data, $where = null)
    {
        $columns = array();

        foreach (array_keys($data) as $column) {
            $columns[] = "`" . $column . "` = ?";
        }
        $columns = implode(", ", $columns);

        $sql = "UPDATE `$table` SET " . $columns . $this->buildWhere($where);

        return $this->prepare($sql);
    }

    /**
     * Insert one row
     *
     * @throw PDOException
     *
     * @param string $table
     * @param array $data
     * @param int|string|null $prev_stmt_id Unique ID to use previous prepared stmt
     *
     * @return void
     */
    function insert($table, $data, $prev_stmt_id = null)
    {

        if(is_object($data)) {
            $data = (array) $data;
        }

        $data = $this->removeNonExistentColumns($table, $data, $prev_stmt_id);

        // regular
        if (empty($prev_stmt_id)) {
            $stmt = $this->getInsertStmt($table, $data);
        } // set and use stmt
        elseif (empty($this->prev_stmt[$prev_stmt_id])) {
            $this->prev_stmt[$prev_stmt_id] = $this->getInsertStmt($table, $data);
            $stmt = $this->prev_stmt[$prev_stmt_id];
        }
        // use previous stmt
        else {
            $stmt = $this->prev_stmt[$prev_stmt_id];
        }

        $stmt->execute(array_values($data));
    }

    /**
     * Update row in table
     * example:
     * update("users",  array("name" => "salebab"), "user_id = ?", $user_id)
     *
     * @throw PDOException
     *
     * @param string $table
     * @param array $data
     * @param mixed $where
     * @param mixed $where_params
     * @param int|string|null $prev_stmt_id Unique ID to use previous prepared stmt
     */
    function update($table, $data, $where, $where_params = array(), $prev_stmt_id = null)
    {
        if (!is_array($where)) {
            $where = array($where);
        }

        if(is_object($data)) {
            $data = (array) $data;
        }

        $data = $this->removeNonExistentColumns($table, $data, $prev_stmt_id);

        // support for scalar param
        if (!is_array($where_params)) {
            $where_params = array($where_params);
        }

        // regular
        if (empty($prev_stmt_id)) {
            $stmt = $this->getUpdateStmt($table, $data, $where);
        } // set and use stmt
        elseif (empty($this->prev_stmt[$prev_stmt_id])) {
            $stmt = $this->getUpdateStmt($table, $data, $where);
        }
        // use previous stmt
        else {
            $stmt = $this->getUpdateStmt($table, $data, $where);
        }


        $stmt->execute(array_merge(array_values($data), $where_params));
    }

    /**
     * Delete rows from table
     *
     * @param string $table
     * @param mixed $where
     * @param string $where_operand AND|OR
     */
    function delete($table, $where, $where_operand = "AND")
    {
        $sql = "DELETE FROM " . $table . $this->buildWhere($where, $where_operand);
        $this->exec($sql);
    }

    /**
     * Count rows in one table - very simple implementation
     *
     * @param string $table
     * @param mixed $where
     * @param array $where_params
     * @return int
     */
    function count($table, $where, $where_params = null)
    {
        $sql = "SELECT COUNT(*) FROM " . $table . $this->buildWhere($where);
        $stmt = $this->executeQuery($sql, $where_params);

        return $stmt->fetchColumn();
    }


    /**
     * Prepare & execute query with params
     *
     * @param $sql
     * @param null $params
     * @return DBStatement
     */
    function executeQuery($sql, $params = null)
    {
        if (!is_array($params) && !is_null($params)) {
            $params = array($params);
        }

        $stmt = $this->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Build where statement for SQL query
     *
     * @param mixed $where
     * @param string $operand AND | OR
     * @return string
     */
    function buildWhere($where, $operand = "AND")
    {

        if (empty($where)) {
            return "";
        }

        if (is_array($where)) {
            $wheres = array();
            foreach ($where as $k => $w) {
                $wheres[] = "(" . $w . ")";
            }
            $where = implode(" $operand ", $wheres);
        }

        return " WHERE " . $where;
    }

    /**
     * Get Database Query Builder
     * @return DBQuery
     */
    function createQuery()
    {
        include_once 'DBQuery.php';
        return new DBQuery($this);
    }

    /**
     * Shortcut for createQuery()->select
     *
     * @param string $statement
     * @return DBQuery
     */
    function select($statement = "")
    {
        return self::createQuery()->select($statement);
    }

    /**
     * Get all columns from table
     *
     * NOTE - Caching is not supported in this version.
     *
     * @param $table
     * @param string $table_alias
     * @param string $use_delimiter
     * @param bool $caching
     * @return array|mixed|null
     */
    function getColumnsFromTable($table, $table_alias = "", $use_delimiter = "_", $caching = false)
    {
        $cache_id = $cache = $columns = null;

        // Using caching?
        /*
		if ($caching) {
            // build ID of cache, that is cachefilename
            $cache_id = "sql_columns_" . $table;
            if ($table_alias) {
                $cache_id .= "_" . $table_alias;
            }

            include_once SYSTEM_DIR . "/libraries/caching/Cache.php";
            $cache = new Cache();
            $columns = $cache->get($cache_id);
        }
		*/
        // No cached data
        if (!$columns) {
            $sql = "DESCRIBE $table";
            $r = $this->executeQuery($sql);
            $columns = $r->fetchAll(self::FETCH_COLUMN);

            // Using table alias?
            // Modify column to "table.column AS alias_column"
            if (!empty($table_alias)) {
                foreach ($columns as $key => $column) {
                    $new_column = $table_alias . "." . $column;
                    if (!empty($use_delimiter)) {
                        $new_column .= " AS " . $table_alias . $use_delimiter . $column;
                    }
                    $columns[$key] = $new_column;
                }
            }
            // Save columns to cache
            /*
               if ($caching) {
                   $cache->save($cache_id, $columns);
               }
               */
        }

        return $columns;
    }

    /**
     * Save data to table
     *
     * @param string $table
     * @param array $data
     * @param string $column_id_name
     * @param string|int $prev_stmt_id
     * @return void
     */
    function save($table, $data, $column_id_name, $prev_stmt_id = null)
    {

        if (isset($data[$column_id_name])) {
            $column_id = (int)$data[$column_id_name];
        } else {
            $column_id = 0;
        }

        if ($column_id > 0) {
            $this->update($table, $data, $column_id_name . " = ?", $column_id, $prev_stmt_id);
        } else {
            $this->insert($table, $data, $prev_stmt_id);
        }
    }


    function setFetchTableNames($option = 1)
    {
        $this->setAttribute(self::ATTR_FETCH_TABLE_NAMES, $option);
        $this->fetch_table_names = $option;
    }
}