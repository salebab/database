<?php
namespace database;

/**
 * Query
 *
 * @author Sasa
 */
class Query
{

    protected $select = array();
    protected $from = array();
    protected $where = array();
    protected $having = array();
    protected $join = array();
    protected $params = array();
    protected $orderBy = array();
    protected $groupBy = array();
    protected $limit = "";


    /**
     * DB instance
     * @var DB
     */
    protected $db;

    /**
     * Database SQL Builder
     *
     * @param DB|null $db
     */
    public function __construct(DB $db = null)
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
     * @return Query
     */
    public function select($statement)
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
     * @return Query
     */
    public function from($statement)
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
     * @return Query
     */
    public function where($statement, $params = null)
    {
        $this->where[] = $statement;
        $this->addParams($params);

        return $this;
    }

    /**
     * Add where in statement
     *
     * @param string $column
     * @param array $params
     *
     * @return Query
     */
    public function whereIn($column, $params)
    {
        $this->prepareWhereInStatement($column, $params, false);
        $this->addParams($params);

        return $this;
    }

    /**
     * Add where not in statement
     *
     * @param $column
     * @param $params
     * @return Query
     */
    public function whereNotIn($column, $params)
    {
        $this->prepareWhereInStatement($column, $params, true);
        $this->addParams($params);

        return $this;
    }

    /**
     * Add statement for HAVING ...
     * @param string $statement
     * @param mixed $params
     * @return Query
     */
    public function having($statement, $params = null)
    {
        $this->having[] = $statement;
        $this->addParams($params);

        return $this;
    }

    /**
     * Add statement for join
     *
     * Examples:
     * $sql->join("INNER JOIN posts p ON p.user_id = u.user_id")
     *
     * @param string $statement
     * @return Query
     */
    public function join($statement)
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
     * @return Query
     */
    public function groupBy($statement)
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
     * @return Query
     */
    public function orderBy($statement)
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
     * @param int $limit
     * @param int $offset
     * @return Query
     */
    public function limit($limit, $offset = null)
    {
        $this->limit = '';

        if(!is_null($offset)) {
            $this->limit = $offset . ', ';
        }

        $this->limit .= $limit;

        return $this;
    }

    /**
     * Returns generated SQL query
     *
     * @return string
     */
    public function getQuery()
    {
        $sql = $this->prepareSelectString();
        $sql .= $this->prepareJoinString();
        $sql .= $this->prepareWhereString();
        $sql .= $this->prepareGroupByString();
        $sql .= $this->prepareHavingString();
        $sql .= $this->prepareOrderByString();
        $sql .= $this->prepareLimitString();

        return $sql;
    }

    /**
     * Returns prepared select string
     *
     * @return string
     */
    private function prepareSelectString()
    {
        if(empty($this->select)) {
            $this->select("*");
        }

        return "SELECT " . implode(", ", $this->select) . " FROM " . implode(", ", $this->from) . " ";
    }

    /**
     * Execute built query
     * This will prepare query, bind params and execute query
     *
     * @return Statement
     */
    public function execute()
    {
        if($this->db === null) {
            $this->db = DB::getInstance();
        }
        return $this->db->execQuery($this);
    }

    /**
     * Clear previous assigned select columns
     * @return Query
     */
    public function clearSelect()
    {
        $this->select = array();

        return $this;
    }

    /**
     * Clear previous assigned group by
     * @return Query
     */
    public function clearGroupBy()
    {
        $this->groupBy = array();

        return $this;
    }

    /**
     * Add param(s) to stack
     *
     * @param array $params
     *
     * @return void
     */
    public function addParams($params)
    {
        if (is_null($params)) {
            return;
        }

        if(!is_array($params)) {
            $params = array($params);
        }

        $this->params = array_merge($this->params, $params);
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Prepares where in statement
     *
     * @param string $column
     * @param array $params
     * @param bool $not_in Use NOT IN statement
     *
     * @return void
     */
    private function prepareWhereInStatement($column, $params, $not_in = false)
    {
        $qm = array_fill(0, count($params), "?");
        $in = ($not_in) ? "NOT IN" : "IN";
        $this->where[] = $column . " " . $in . " (" . implode(", ", $qm) . ")";
    }

    /**
     * Returns prepared join string
     *
     * @return string
     */
    private function prepareJoinString()
    {
        if (!empty($this->join)) {
            return implode(" ", $this->join) . " ";
        }

        return '';
    }

    /**
     * Returns prepared where string
     *
     * @return string
     */
    private function prepareWhereString()
    {
        if (!empty($this->where)) {
            return "WHERE " . implode(" AND ", $this->where) . " ";
        }

        return '';
    }

    /**
     * Returns prepared group by string
     *
     * @return string
     */
    private function prepareGroupByString()
    {
        if (!empty($this->groupBy)) {
            return "GROUP BY " . implode(", ", $this->groupBy) . " ";
        }

        return '';
    }

    /**
     * Returns prepared having string
     *
     * @return string
     */
    private function prepareHavingString()
    {
        if (!empty($this->having)) {
            return "HAVING " . implode(", ", $this->having) . " ";
        }

        return '';
    }

    /**
     * Returns prepared order by string
     *
     * @return string
     */
    private function prepareOrderByString()
    {
        if (!empty($this->orderBy)) {
            return "ORDER BY " . implode(", ", $this->orderBy) . " ";
        }

        return '';
    }

    /**
     * Returns prepared limit string
     *
     * @return string
     */
    private function prepareLimitString()
    {
        if (!empty($this->limit)) {
            return "LIMIT " . $this->limit;
        }

        return '';
    }
}
