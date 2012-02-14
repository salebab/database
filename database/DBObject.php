<?php
/**
 * DBObject
 *
 * @author: Aleksandar Babic <salebab@gmail.com>
 */

class DBObject extends stdClass {

    public $ctime;
    public $mtime;
    public $is_active;

    /**
     * Get formated created time of object
     *
     * @param $format
     * @return string|NULL
     */
    public function getCreatedTime($format = DATE_FORMAT) {
        return !empty($this->ctime) ? date($format, $this->ctime) : NULL;
    }

    /**
     * Get formated modified time of object
     *
     * @param string $format
     * @return string|NULL
     */
    public function getModifiedTime($format = DATE_FORMAT) {
        return !empty($this->mtime) ? date($format, $this->mtime) : NULL;
    }

    /**
     * Returns active status as string
     *
     * @param string $yes
     * @param string $no
     * @return string
     */
    public function activeStatus($yes = "Yes", $no = "No") {
        return $this->is_active ? $yes : $no;
    }
}
