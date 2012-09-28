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
     * ONLY if $prev_stmt is used
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


    /**
     * Positive if PDO::FETCH_TABLE_NAMES is used
     * @var int
     */
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
     * @param mixed $stmt_key
     * @return array
     */
    private function removeNonExistentColumns($table, &$data, $stmt_key = null)
    {
        if ($this->check_columns) {

            // use previous columns or get new
            if (!empty($stmt_key) && empty($this->prev_columns[$stmt_key])) {
                $this->prev_columns[$stmt_key] = $this->getColumnsFromTable($table);
                $columns = $this->prev_columns[$stmt_key];
            } elseif (!empty($stmt_key) && !empty($this->prev_columns[$stmt_key])) {
                $columns = $this->prev_columns[$stmt_key];
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
     * @param int|string|null $stmt_key Unique key to use previous prepared stmt
     *
     * @return void
     */
    function insert($table, $data, $stmt_key = null)
    {
        if(is_object($data)) {
            $data = (array) $data;
        }

        $data = $this->removeNonExistentColumns($table, $data, $stmt_key);

        if (empty($stmt_key)) {
            $stmt = $this->getInsertStmt($table, $data);
        }
        elseif (empty($this->prev_stmt[$stmt_key])) {
            $this->prev_stmt[$stmt_key] = $this->getInsertStmt($table, $data);
            $stmt = $this->prev_stmt[$stmt_key];
        }
        else {
            $stmt = $this->prev_stmt[$stmt_key];
        }

        $stmt->execute(array_values($data));
    }

    /**
     * Update row in table, optionally use previous prepared stmt by stmt_key
     *
     * @throw PDOException
     *
     * @param string $table
     * @param array $data
     * @param mixed $where
     * @param mixed|array $where_params
     * @param int|string|null $stmt_key Unique key to use previous prepared stmt
     */
    function update($table, $data, $where, $where_params = array(), $stmt_key = null)
    {
        if (!is_array($where)) {
            $where = array($where);
        }

        if(is_object($data)) {
            $data = (array) $data;
        }

        $data = $this->removeNonExistentColumns($table, $data, $stmt_key);

        // support for scalar param
        if (!is_array($where_params)) {
            $where_params = array($where_params);
        }

        if (empty($stmt_key)) {
            $stmt = $this->getUpdateStmt($table, $data, $where);
        }
        elseif (empty($this->prev_stmt[$stmt_key])) {
            $stmt = $this->getUpdateStmt($table, $data, $where);
            $this->prev_stmt[$stmt_key] = $stmt;
        }
        else {
            $stmt = $this->prev_stmt[$stmt_key];
        }


        $stmt->execute(array_merge(array_values($data), $where_params));
    }

    /**
     * Delete rows from table
     *
     * @throw PDOException
     *
     * @param string $table
     * @param mixed $where
     * @param mixed $where_params
     */
    function delete($table, $where, $where_params)
    {
        $sql = "DELETE FROM " . $table . $this->buildWhere($where);
        $this->executeQuery($sql, $where_params);
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
     * @throw PDOException
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
     * @throw PDOException
     *
     * @param $table
     * @return array
     */
    function getColumnsFromTable($table)
    {
        $sql = "DESCRIBE $table";
        
        return $this->executeQuery($sql)
            ->fetchAll(self::FETCH_COLUMN);
    }

    /**
     * Save data to table
     *
     * @throw PDOException
     *
     * @param string $table
     * @param array $data
     * @param string $primary_key Name of primary key column
     * @param string|int $stmt_key
     * @return void
     */
    function save($table, $data, $primary_key, $stmt_key = null)
    {
        // Update if primary key exists in data set or insert new row
        if (!empty($data[$primary_key])) {
            $this->update($table, $data, $primary_key . " = ?", $data[$primary_key], $stmt_key);
        }
        else {
            $this->insert($table, $data, $stmt_key);
        }
    }


    /**
     * Set fetch table names attribute
     *
     * @param int $option 1 or 0
     */
    function setFetchTableNames($option = 1)
    {
        $this->setAttribute(self::ATTR_FETCH_TABLE_NAMES, $option);
        $this->fetch_table_names = $option;
    }
}