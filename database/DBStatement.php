<?php
/**
 * DBStatement
 *
 * @author Sasa
 */
class DBStatement extends PDOStatement
{

    public $delimiter = ".";

    /**
     * Instance of DBWrapper class
     *
     * @var DBWrapper
     */
    protected $db;

    /**
     * Last fetched row
     *
     * @var array
     */
    public $last_row;

    /**
     *
     * @param DBWrapper $db
     */
    protected function __construct(DBWrapper $db)
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
     * @param string $from_table
     * @param int $fetch_from Fetch data from next or last fetched row. DBWrapper::FETCH_FROM_NEXT_ROW or DBWrapper::FETCH_FROM_LAST_ROW
     * @return object $object
     */
    function fetchInto($object, $from_table = "", $fetch_from = DBWrapper::FETCH_FROM_NEXT_ROW)
    {

        if ($from_table == "") {
            $this->setFetchMode(DBWrapper::FETCH_INTO, $object);
            return $this->fetch();
        } elseif ($fetch_from == DBWrapper::FETCH_FROM_NEXT_ROW) {
            $this->setFetchMode(DBWrapper::FETCH_ASSOC);
            $this->last_row = $this->fetch();
        }

        if (empty($this->last_row)) {
            return NULL;
        }

        $table = "";
        // Assign values of last_row to object's properties
        foreach ($this->last_row as $key => $value) {

            if ($this->db->fetch_table_names) {
                list($table, $column) = explode($this->delimiter, $key, 2);
            } else {
                $column = $key;
            }

            // assign
            if ($from_table == $table OR empty($table)) {
                $object->{$column} = $value;
                unset($this->last_row[$key]);
            } // For aliases or functions (count()), assign to first object
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
     * This is shortcut for fetchInto($object, $from_table, DBWrapper::FETCH_FROM_LAST_ROW);
     *
     * @param object $object
     * @param string $from_table MUST NOT BE EMPTY
     * @return object $object
     */
    function fetchIntoFromLastRow($object, $from_table)
    {
        return $this->fetchInto($object, $from_table, DBWrapper::FETCH_FROM_LAST_ROW);
    }

    /**
     * Fetch collection of objects
     * All rows will be fetch into cloned object
     *
     * $obj and $this is passed to event
     *
     * @param string|object $class_name
     * @param string $from_table
     * @return array
     */
    function fetchCollection($class_name, $from_table = "")
    {
        $collection = array();

        if (!$this->rowCount()) {
            return $collection;
        }

        /* backward compatibility, you can use object instead of class name */
        if (is_object($class_name)) {
            $class_name = get_class($class_name);
        }

        while ($obj = $this->fetchInto(new $class_name, $from_table)) {
            $collection[] = $obj;
        }
        return $collection;
    }

    /**
     * Get value from column, by column name
     *
     * @param string $column_name
     * @return mixed
     */
    function getColumnValue($column_name)
    {
        return $this->last_row[$column_name];
    }

    function closeCursor()
    {
        $this->last_row = null;
        return parent::closeCursor();
    }

}
