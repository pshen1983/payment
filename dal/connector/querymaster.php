<?php defined('SYSPATH') or die('No direct access allowed.');

/**
 *
 * Enter description here ...
 * @author indochino
 *
 */
class DAO_QueryMaster {

    private $errors = array();
    private $query = '';
    private $and = false;
    private $is_insert = false;
    private $connection = null;

// ============================================================================= transactional start

    private static $tran_connection = null;

    public static function start_transaction() {
        if (!isset(self::$tran_connection)) {
            self::$tran_connection = new DAO_QueryMaster(FALSE);
            mysqli_autocommit(self::$tran_connection, FALSE);
        }

        return self::$tran_connection;
    }

    public static function commit($roll_back=TRUE, $end_transaction=TRUE) {
        $transactions = self::start_transaction();

        $errors = $transactions->get_errors();
        $qeuryResults = empty($errors);

        if ($qeuryResults) {
            mysqli_commit(self::$tran_connection);
        } else if ($roll_back) {
            mysqli_rollback(self::$tran_connection);
        }

        if ($end_transaction) {
            mysqli_close(self::$tran_connection);
            self::$tran_connection = NULL;
        }

        return $qeuryResults;
    }

    public static function end_transaction() {
        self::$tran_connection = NULL;
    }

// =============================================================================== transactional end

    public function __construct($persist=TRUE) {
        if (isset(self::$tran_connection)) {
            return self::$tran_connection;
        }

        $db_host = External_Config_Indochino::DB_HOST;
        $db_user = External_Config_Indochino::DB_USER;
        $db_pass = External_Config_Indochino::DB_PASS;
        $db_sche = External_Config_Indochino::DB_NAME;

        if ($persist) { $db_host = 'p:'.$db_host; }

        $this->connection = mysqli_connect( $db_host, $db_user, $db_pass, $db_sche );

        if( !$this->connection->connect_errno ) {
            $this->connection->query("SET character_set_results=utf8");
            $this->connection->query("SET character_set_client=utf8");
            $this->connection->query("SET character_set_connection=utf8");
        } else {
            throw new Exception('Cannot establish db connection - '.$db_host, '1001');
        }
    }

    public function insert_batch($inserts, $table) {
        $this->is_insert = true;
        $this->and = false;

        $map = $inserts[0];
        $fileds = '(';
        foreach ($map as $key=>$val)
        {
            $fileds .= $key . ',';
        }
        $fileds = rtrim($fileds, ',') . ')';

        $values = '';
        foreach ($inserts as $insert) {
            $values .= '(';
            foreach ($insert as $key=>$val)
            {
                $values .= $this->checkNull($val) . ',';
            }
            $values = rtrim($values, ',') . '),';
        }
        $values = rtrim($values, ',');

        $this->query = "INSERT INTO $table $fileds VALUES $values";

        return $this;
    }

    public function insert($inserts, $table) {
        $this->is_insert = true;
        $this->and = false;

        $fileds = '(';
        $values = '(';
        foreach ($inserts as $key=>$val)
        {
            if ( isset($val) )
            {
                $fileds .= $key . ',';
                $values .= $this->checkNull($val) . ',';
            }
        }
        $fileds = rtrim($fileds, ',') . ')';
        $values = rtrim($values, ',') . ')';

        $this->query = "INSERT INTO $table $fileds VALUES $values";

        return $this;
    }

    public function update($set, $table, $expression=FALSE) {
        $this->is_insert = false;
        $this->and = false;

        $update = "UPDATE $table SET ";
        foreach ($set as $key=>$val) {
            if ($expression) {
                $update.= $key.'='.$val.',';
            } else {
                $update.= $key.'='.$this->checkNull($val).',';
            }
        }

        $this->query = rtrim($update, ',');

        return $this;
    }

    public function select($fields, $table) {
        $this->is_insert = false;
        $this->and = false;

        $select = 'SELECT ';
        if (is_array($fields)) {
            foreach ($fields as $field) {
                $select.= $field.',';
            }
            $select = rtrim($select, ',');
        } else {
            $select.= trim($fields);
        }

        $this->query = $select.' FROM '.$table;

        return $this;
    }

    public function delete($table) {
        $this->is_insert = false;
        $this->and = false;

        $this->query = "DELETE FROM $table";

        return $this;
    }

    public function where($field, $value, $operator='=', $or=FALSE) {
        if ($this->and) {
            $where = $or ? ' OR ' : ' AND ';
        } else {
            $where = ' WHERE ';
        }

        $where.= $field.' '.$operator.' '.($value==='NULL' ? 'NULL' : $this->checkNull($value));

        $this->and = true;

        $this->query.= $where;

        return $this;
    }

    /**
     * Adds 'field IS NULL' or 'IS NOT NULL' to the query
     * @author Ramesh, 9/17/2014
     */
    public function where_null($field, $check_null=TRUE, $or=FALSE) {
        if ($this->and) {
            $where = $or ? ' OR ' : ' AND ';
        } else {
            $where = ' WHERE ';
        }

        $where .= $field.' IS';
        if ($check_null == FALSE) {
            $where .= $where.' NOT';
        }
        $where .= $where.' NULL';

        $this->and = true;

        $this->query.= $where;

        return $this;
    }

    /**
     * Appends a like sql clause to the query.
     * If you need to search for strings that begin with a value, set $lead to TRUE.
     * If you need to search for strings that end with a value, set $trail to TRUE.
     * You can also combine the $lead and $trail to search for strings that contain a value anywhere.
     * @author Ramesh, 9/16/2014
     */
    public function like($field, $value, $lead=TURE, $trail=TRUE, $is_like=TRUE, $or=FALSE) {
        if ($this->and) {
            $like = $or ? ' OR ' : ' AND ';
        } else {
            $like = ' WHERE ';
        }

        $operator = $is_like ? 'LIKE' : 'NOT LIKE';

        $like .= "$field $operator '";
        if ($trail) {
            $like .= "%";// need the % in front of the string to search for values that trail with value
        }
        $like .= $value;
        if ($lead) {
            $like .= "%";// need the % in end of the string to search for values that begin with value
        }
        $like .= "'";

        $this->and = true;

        $this->query.= $like;

        return $this;
    }

    public function in($field, $range, $is_in=TRUE, $or=FALSE) {
        if (empty($range)) { $range = array(0); }

        if ($this->and) {
            $in = $or ? ' OR ' : ' AND ';
        } else {
            $in = ' WHERE ';
        }

        $operator = $is_in ? 'IN' : 'NOT IN';

        $in.= "`$field` $operator (";
        foreach ($range as $value) {
            $in.= "'".$value."',";
        }
        $in = rtrim($in, ',').')';

        $this->and = true;

        $this->query.= $in;

        return $this;
    }

    public function limit($start, $size) {
        $this->query.= " LIMIT $start, $size";

        return $this;
    }

    public function order($field, $desc=false) {
        $this->query.= " ORDER BY $field";

        if ($desc) {
            $this->query.= " DESC";
        }

        return $this;
    }

    public function group($field) {
        $this->query.= " GROUP BY $field";

        return $this;
    }

    public function adhoc_query($query) {
    	$this->query = $query;

        return $this;
    }

    public function escape($input){
        return (isset($input) ? "'". $this->connection->real_escape_string($input) . "'" : "");
    }

    public function execute() {
        if (External_Config_Indochino::DB_LOG_LEVEL>=2) {
            Kohana::$log->add(Log::DATABASE, $this->query)->write();
        }

        if ($this->is_insert) {
            if ($this->connection->query($this->query)) {
                $result = $this->connection->insert_id;
            } else {
                $result = -1;
                array_push($this->errors, mysqli_error($this->connection));
            }
        } else {
            $result = $this->connection->query($this->query);
            if (!($result)) {
                array_push($this->errors, mysqli_error($this->connection));
            }
        }

        if (External_Config_Indochino::DB_LOG_LEVEL>=1 && !empty($this->errors)) {
        if (External_Config_Indochino::DB_LOG_LEVEL<2) {
                Kohana::$log->add(Log::DATABASE, $this->query)->write();
            }
            Kohana::$log->add(Log::DATABASE, '[ERROR] - '.json_encode($this->errors))->write();
        }

        return $result;
    }

    public function find_one() {
        $this->query.= " LIMIT 1";

        $result = $this->execute();

        if ($result) {
            $result = $result->fetch_array(MYSQLI_ASSOC);
        } else {
            $result = null;
        }

        return $result;
    }

    public function ad_hoc_count_query($query_str) {
        $this->query = $query_str;
        $res = $this->find_one();

        return $res['count'];
    }

    public function find_all() {
        $result = $this->execute();

        if ($result) {
            $res = array();
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                array_push($res, $row);
            }
            return $res;
        }

        return array();
    }

    public function get_errors() {
        return $this->errors;
    }

// ========================================================================================= private

    private function checkNull($input)
    {
        return (isset($input) ? "'". $this->connection->real_escape_string($input) . "'" : "NULL");
    }
}
