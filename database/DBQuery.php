<?php

/**
 * DBQuery
 *
 * @author Sasa
 */
class DBQuery
{

    protected $select = array();
    protected $from = array();
    protected $where = array();
    protected $having = array();
    protected $join = array();
    public $params = array();
    protected $orderBy = array();
    protected $groupBy = array();
    protected $limit = "";


    /**
     * @var DBWrapper
     */
    protected $db;

    /**
     * Database SQL Builder
     *
     * @param DBWrapper $db
     */
    function __construct(DBWrapper $db)
    {
        $this->db = $db;
    }

    /**
     * Add statement for select - SELECT [?] FROM ...
     *
     * Examples:
     * $sql->select("u.*")
     *     ->select("b.*, COUNT(*) as total")
     *
     * @param string $statement
     * @return DBQuery
     */
    function select($statement)
    {
        $this->select[] = $statement;
        return $this;
    }

    /**
     * Add statement for from - SELECT * FROM [?] ...
     *
     * Examples:
     * $sql->from("users");
     * $sql->from("users u, posts p");
     *
     * @param string $statement
     * @return DBQuery
     */
    function from($statement)
    {
        $this->from[] = $statement;
        return $this;
    }

    /**
     * Add statement for where - ... WHERE [?] ...
     *
     * Examples:
     * $sql->where("user_id = ?", $user_id);
     * $sql->where("u.registered > ? AND (u.is_active = ? OR u.column IS NOT NULL)", array($registered, 1));
     *
     * @param string $statement
     * @param mixed $params
     * @return DBQuery
     */
    function where($statement, $params = null)
    {
        $this->where[] = $statement;

        // add param(s) to stack
        if ($params !== null) {
            if (is_array($params)) {
                foreach ($params as $param) {
                    $this->params[] = $param;
                }
            } else {
                $this->params[] = $params;
            }
        }

        return $this;
    }

    /**
     * @param string $column
     * @param array $params
     * @param bool $not_in Use NOT IN statement
     * @return DBQuery
     */
    function whereIn($column, $params, $not_in = false)
    {
        $qm = array_fill(0, count($params), "?");
        $in = ($not_in) ? "NOT IN" : "IN";
        $this->where[] = $column . " " . $in . " (" . implode(", ", $qm) . ")";

        foreach ($params as $param) {
            $this->params[] = $param;
        }

        return $this;
    }

    /**
     * Where NOT IN statement
     *
     * @param $column
     * @param $params
     * @return DBQuery
     */
    function whereNotIn($column, $params)
    {
        return $this->whereIn($column, $params, true);
    }

    /**
     * Add statement for HAVING ...
     * @param string $statement
     * @param mixed $params
     * @return DBQuery
     */
    function having($statement, $params = null)
    {
        $this->having[] = $statement;

        // add param(s) to stack
        if ($params !== null) {
            if (is_array($params)) {
                $this->params = array_merge($this->params, $params);
            } else {
                $this->params[] = $params;
            }
        }

        return $this;
    }

    /**
     * Add statement for join
     *
     * Examples:
     * $sql->join("INNER JOIN posts p ON p.user_id = u.user_id")
     *
     * @param string $statement
     * @return DBQuery
     */
    function join($statement)
    {
        $this->join[] = $statement;
        return $this;
    }

    /**
     * Add statement for group - GROUP BY [...]
     *
     * Examples:
     * $sql->groupBy("user_id");
     * $sql->groupBy("u.is_active, p.post_id");
     *
     * @param string $statement
     * @return DBQuery
     */
    function groupBy($statement)
    {
        $this->groupBy[] = $statement;
        return $this;
    }

    /**
     * Add statement for order - ORDER BY [...]
     *
     * Examples:
     * $sql->orderBy("registered");
     * $sql->orderBy("is_active, registered DESC");
     *
     * @param string $statement
     * @return DBQuery
     */
    function orderBy($statement)
    {
        $this->orderBy[] = $statement;
        return $this;
    }

    /**
     * Add statement for limit - LIMIT [...]
     *
     * Examples:
     * $sql->limit(30);
     * $sql->limit(30,30);
     *
     * @param int $param1
     * @param int $param2
     * @return DBQuery
     */
    function limit($param1, $param2 = null)
    {
        $this->limit = $param1;
        if (!is_null($param2)) {
            $this->limit .= ", " . $param2;
        }
        return $this;
    }


    function getQuery()
    {
        if (empty($this->select)) {
            $this->select("*");
        }
        $sql = "SELECT " . implode(", ", $this->select) . " "
            . "FROM " . implode(", ", $this->from) . " ";
        if (!empty($this->join)) {
            $sql .= implode(" ", $this->join) . " ";
        }
        if (!empty($this->where)) {
            $sql .= "WHERE ";
            $sql .= implode(" AND ", $this->where) . " ";
        }
        if (!empty($this->groupBy)) {
            $sql .= "GROUP BY ";
            $sql .= implode(", ", $this->groupBy) . " ";
        }
        if (!empty($this->having)) {
            $sql .= "HAVING ";
            $sql .= implode(", ", $this->having) . " ";
        }
        if (!empty($this->orderBy)) {
            $sql .= "ORDER BY " . implode(", ", $this->orderBy) . " ";
        }
        if (!empty($this->limit)) {
            $sql .= "LIMIT " . $this->limit;
        }
        return $sql;
    }

    /**
     * Execute builded query
     * This will prepare query, bind params and execute query
     *
     * @return DBStatement
     */
    function execute()
    {
        return $this->db->executeQuery($this->getQuery(), $this->params);
    }

    /**
     * Clear previous assigned select columns
     * @return DBQuery
     */
    function clearSelect()
    {
        $this->select = array();
        return $this;
    }

    /**
     * Clear previous assigned groupby
     * @return DBQuery
     */
    function clearGroupBy()
    {
        $this->groupBy = array();
        return $this;
    }

}