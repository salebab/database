<?php
/**
 * DBStatement
 *
 * @author Sasa
 */
class DBStatement extends PDOStatement {

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
     * @var type 
     */
    public $last_row;
    
    /**
     *
     * @param DB $db
     */
    protected function __construct($db) {
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
     * @param int $fetch_from Fetch data from next or last fetched row. DB::FETCH_FROM_NEXT_ROW or DB::FETCH_FROM_LAST_ROW
     * @return object $object
     */
    function fetchInto($object, $from_table = "", $fetch_from = DB::FETCH_FROM_NEXT_ROW) {

        if($from_table == "") {
            $this->setFetchMode(DB::FETCH_INTO, $object);
            return $this->fetch();
        } 
        elseif($fetch_from == DB::FETCH_FROM_NEXT_ROW) {
            $this->setFetchMode(DB::FETCH_ASSOC);
            $this->last_row = $this->fetch();
        }

        if(empty($this->last_row)) {
            return NULL;
        }
        // Assign values of last_row to object's properites
        foreach($this->last_row as $key => $value) {

            // check if ATTR_FETCH_TABLE_NAMES is used
            if(strpos($key, $this->delimiter) !== FALSE) {
                list($table, $column) = explode($this->delimiter, $key, 2);
            } else {
                $column = $key;
                $table = "";
            }

            // assign
            if($from_table == $table OR empty($table)) {
                $object->{$column} = $value;
                unset($this->last_row[$key]);
            }

            // special assign, without tablename, eq - for count or some other function
            // example: .store_total_books become store.total_books
            elseif($from_table != "" && $table == "" && substr($key, 1, strlen($from_table)) == $from_table) {
                $column = substr($key, strlen($from_table)+2);
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
     * @param string $from_table MUST NOT BE EMPTY
     * @return object $object 
     */
    function fetchIntoFromLastRow($object, $from_table) {
        return $this->fetchInto($object, $from_table, DB::FETCH_FROM_LAST_ROW);
    }

    /**
     * Fetch collection of objects
     * All rows will be fetch into cloned object
     *
     * $obj and $this is passed to event
     *
     * @param $object
     * @param string $from_table
     * @return array
     */
    function fetchCollection($object, $from_table = "") {
        if(!$this->rowCount()) {
            return array();
        }
        $collection = array();
        while($obj = $this->fetchInto(clone $object, $from_table)) {
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
    function getColumnValue($column_name) {
        return $this->last_row[$column_name];
    }
    
    function closeCursor() {
        $this->last_row = null;
        return parent::closeCursor();
    }
    
}

?>