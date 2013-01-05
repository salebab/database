<?php
namespace database;

/**
 * Statement
 *
 * @author Sasa
 */
class Statement extends \PDOStatement
{

    public $delimiter = ".";

    /**
     * Instance of DB class
     *
     * @var DB
     */
    protected $db;

    /**
     * Last fetched row
     *
     * @var array
     */
    public $last_row;

    /**
     * @param DB $db
     */
    protected function __construct(DB $db)
    {
        $this->db = $db;
    }


    /**
     * Fetch data into object's properties.
     * If $from_table is defined, only data from that table will be assigned
     *
     * Note: After value is assigned to property, it will be unset from last_row
     *
     * @param object $object
     * @param string $from_table If isn't used, method will return all data in one object
     * @param int $fetch_from Fetch data from next or last fetched row. DB::FETCH_FROM_NEXT_ROW or DB::FETCH_FROM_LAST_ROW
     * @return object|NULL
     */
    function fetchInto($object, $from_table = "", $fetch_from = DB::FETCH_FROM_NEXT_ROW)
    {
        if ($from_table == "") {
            $this->db->setFetchTableNames(0);
            $this->setFetchMode(DB::FETCH_INTO, $object);
            return $this->fetch();
        } elseif ($fetch_from == DB::FETCH_FROM_NEXT_ROW) {
            $this->setFetchMode(DB::FETCH_ASSOC);
            $this->last_row = $this->fetch();
        }

        if (empty($this->last_row)) {
            return null;
        }

        $table = "";
        // Copy values of last_row to object's properties
        foreach ($this->last_row as $key => $value) {

            if ($this->db->fetch_table_names) {
                list($table, $column) = explode($this->delimiter, $key, 2);
            } else {
                $column = $key;
            }

            // copy
            if ($from_table == $table OR empty($table)) {
                $object->{$column} = $value;
                unset($this->last_row[$key]);
            }
            // For aliases or functions (count()), assign to first object
            // example: .store_total_books become store.total_books
            elseif ($from_table != "" && $table == "" && substr($key, 1, strlen($from_table)) == $from_table) {
                $column = substr($key, strlen($from_table) + 2);
                $object->{$column} = $value;
                unset($this->last_row[$key]);
            }
        }

        return $object;
    }

    /**
     * Fetch data into object from last fetched row.
     * This is shortcut for fetchInto($object, $from_table, DB::FETCH_FROM_LAST_ROW);
     *
     * @param object $object
     * @param string $from_table
     * @return object|NULL
     */
    function fetchIntoFromLastRow($object, $from_table)
    {
        return $this->fetchInto($object, $from_table, DB::FETCH_FROM_LAST_ROW);
    }

    /**
     * Fetch collection of objects (do the some thing as fetchAll)
     *
     * @param string|object $class_name
     * @return array
     */
    function fetchCollection($class_name = "stdClass")
    {
        /* backward compatibility, you can use object instead of class name */
        if (is_object($class_name)) {
            $class_name = get_class($class_name);
        }

        return $this->fetchAll(DB::FETCH_CLASS, $class_name);
    }

    /**
     * Get value from column, from last row
     *
     * @param string $column_name
     * @return mixed|NULL
     */
    function getColumnValue($column_name)
    {
        return isset($this->last_row[$column_name]) ? $this->last_row[$column_name] : null;
    }

    function closeCursor()
    {
        $this->last_row = null;
        return parent::closeCursor();
    }

}
