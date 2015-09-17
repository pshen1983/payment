<?php defined('SYSPATH') or die('No direct access allowed.');

abstract class ConfoneDaoBase {

    /**
     * the database column name to value holder array.
     * 
     * ( e.g. for a database with 3 columns: "id, col1, col2", 
     *   $var should have $var['id'], $var['col1'], $var['col2'] 3 key => value entries.
     *   $var should be set in the constructor of each subclass implementation.)
     *   
     * @var array
     */
    protected $var = array();

    protected $update = array();

    protected $from_db = FALSE;

    /**
     * constructor to make an address dao
     * 
     * @param integer $id - database id
     */
    public function __construct( $id=0 ) {
        if ($id==0) { $this->init(); }
        else { $this->retrieve($id); }
    }

    /**
     * get list of objects in range.
     * 
     * @param unknown_type $ids
     */
    public static function get_range($ids, $order='id', $desc=TRUE) {
        if (empty($ids)) return array();

        $class = get_called_class();

        if (count($ids)<51 && self::is_id_caching()) {
            $objs = array();
            foreach ($ids as $id) {
                if (empty($id)) { continue; }
                $obj = new $class($id);
                $objs[] = $obj;
            }
        } else {
            $builder = new DAO_QueryMaster();
            $res = $builder->select('*', $class::$table)
                           ->in('id', $ids)
                           ->order($order, $desc)
                           ->find_all();
    
            $objs = self::new_from_query_result_list($res, get_called_class());
        }

        return $objs;
    }

    /**
     * Retrieve an object from database based on id
     * @param $id - the database primary key id
     */
    protected function retrieve($id) {
        $id_column = $this->get_id_column_name();

        $cacher = DAO_Cacher::instance();
        if (self::is_id_caching() && $cacher->exist($this->get_table_name().'.'.$id)) {
            $res = $cacher->get($this->get_table_name().'.'.$id);
        } else {
            $query = new DAO_QueryMaster();
            $res = $query->select('*', $this->get_table_name())
                         ->where($id_column, $id)
                         ->find_one();

            if (self::is_id_caching()) {
                $cacher->set($this->get_table_name().'.'.$id, $res);
            }
        }

        if (isset($res) && $res) {
            $this->var = $res;
            $this->from_db = TRUE;
        } else {
            $this->init();
        }
    }

    /**
     * Saves a list of documents of the same type ...
     * 
     * @param unknown_type $daos
     */
    public static function save_batch($daos) {
        if (empty($daos)) { return FALSE; }

        $inserts = array();
        foreach ($daos as $dao) {
            $inserts[] = $dao->to_array(array($dao->get_id_column_name()));
        }

        $builder = new DAO_QueryMaster();
        $res = $builder->insert_batch($inserts, $dao->get_table_name())
                       ->execute();

        return $res;
    }

    /**
     * Saves the object to database, if primary key value exists do update, if not do insert.
     */
    final public function save() {
        // get the primary key value from abstract implementation of sub class
        //
        $id = $this->var[$this->get_id_column_name()];

        if ( isset($id) && !empty($id) && $id!=0 ) {
            $this->action_before_update();
            return $this->update();
        }
        else {
            $this->action_before_insert();
            return $this->insert();
        }
    }

    final public function delete() {
        $this->action_before_delete();
        if (self::is_id_caching()) {
            $cache_key = $this->get_table_name().'.'.$this->var[$this->get_id_column_name()];
            DAO_Cacher::instance()->delete($cache_key);
        }
        $this->do_delete();
    }

    protected function do_delete() {
        // get the primary key value from abstract implementation of sub class
        //
        $id = $this->var[$this->get_id_column_name()];

        $builder = new DAO_QueryMaster();
        $res = $builder->delete($this->get_table_name())
                       ->where($this->get_id_column_name(), $id)
                       ->execute();
        if ($res) {
            $this->from_db = FALSE;
        }

        return $res;
    }

    /**
     * Convert the DAO to an array presentation with keys to be the column names.
     * 
     * @param array $skips - skipped columns in the return array.
     */
    public function to_array($skips=array()) {
        $rv = $this->var;
        foreach ($skips as $skip) {
            unset($rv[$skip]);
        }

        // remove all 'couch_xxx_id' during migration period, those columns should be removed.
        foreach ($rv as $key=>$val) {
            if (strpos($key, 'couch_') !== FALSE) {
            unset($rv[$key]);
            }
        }

        return $rv;
    }

    /**
     * Check if this dao is loaded from database.
     */
    public function is_from_db() {
        return $this->from_db;
    }

    /**
     * Insert an object to database
     */
    private function insert() {
        $id_column = $this->get_id_column_name();

        $fields = $this->var;
        unset($fields[$id_column]);

        $query = new DAO_QueryMaster();
        $res = $query->insert($fields, $this->get_table_name())
                     ->execute();

        $this->update = array_fill_keys(array_values($this->update), false);

        if ($res!=-1) {
            $this->var[$id_column] = $res;
            $this->from_db = TRUE;
        } else {
            $message = '';
            foreach ($query->get_errors() as $error) {
                $message .= $error.' | ';
            }
            Kohana::$log->add(Log::ERROR, '[DB ERROR] Insert Failed: ' . $message);
        }

        return $res!=-1;
    }

    /**
     * update the database row of the object
     */
    private function update() {
        $id_column = $this->get_id_column_name();

        $set = array();
        foreach ($this->update as $key=>$val) {
            if ($val) {
                $set[$key] = $this->var[$key];
            }
        }

        if (!empty($set)) {
            $builder = new DAO_QueryMaster();
            $res = $builder->update($set, $this->get_table_name())
                           ->where($id_column, $this->var[$id_column])
                           ->execute();
            if ($res) {
                $this->update = array_fill_keys(array_values($this->update), false);
                if (self::is_id_caching()) {
                    DAO_Cacher::instance()->delete(
                                    $this->get_table_name().'.'.$this->var[$id_column]);
                }
            }
        } else {
            $res = true;
        }

        return $res;
    }

    /**
     * Enter description here ...
     * 
     * @param unknown_type $dao
     */
    public static function copy($dao) {
        $class = get_called_class();
        $id_column = $dao->get_id_column_name();

        $rv = new $class;
        foreach ($dao->var as $key=>$val) {
            if (strpos($key, 'couch_') === FALSE) {
                $rv->var[$key] = $val;
            }
        }
        $rv->var[$id_column] = 0;

        return $rv;
    }

    /**
     * Enter description here ...
     * @param unknown_type $res
     * @param unknown_type $class
     */
    protected static function new_from_query_result($res, $class) {
        $object = null;
        if ($res) {
            $object = new $class;
            $object->var = $res;
            $object->from_db = TRUE;
        }

        return $object;
    }

    /**
     * Enter description here ...
     * @param unknown_type $res
     * @param unknown_type $class
     */
    protected static function new_from_query_result_list($res, $class) {
        $objects = array();
        if (isset($res)) {
            foreach ($res as $row) {
                $object = new $class;
                $object->var = $row;
                $object->from_db = TRUE;
                array_push($objects, $object);
            }
        }

        return $objects;
    }

    private static function is_id_caching() {
    	return External_Config_Indochino::DB_CACHE_ON && static::cache_by_id();
    }

//====================================== abstract functions ======================================

    /**
     * Enter description here ...
     */
    protected function action_before_insert() {}

    /**
     * Enter description here ...
     */
    protected function action_before_update() {}

    /**
     * Enter description here ...
     */
    protected function action_before_delete() {}

    /**
     * Enter description here ...
     */
    protected static function cache_by_id() { return FALSE; }

    /**
     * Enter description here ...
     */
    abstract protected function init();

    /**
     * returns the database table name of the object
     */
    abstract protected function get_table_name();

    /**
     * returns the database primary key id column name
     */
    abstract protected function get_id_column_name();
}