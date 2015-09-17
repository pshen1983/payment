<?php
$db_host = "127.0.0.1";
$db_user = "confone";
$db_pass = "confonepass";
$db_sche = "confone";
$base_class = 'ConfoneDaoBase';
$target_folder = '../../../../dal/base/';

$conn = mysqli_connect("p:".$db_host, $db_user, $db_pass, $db_sche);
$sql = "SHOW TABLES";
$tableResult = $conn->query($sql);

while ($tableRow = $tableResult->fetch_array(MYSQLI_ASSOC)) {
    $table = reset($tableRow);

    $sql = "SHOW COLUMNS FROM $table";
    $result = $conn->query($sql);
    $fields = array();
    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
        array_push($fields, $row["Field"]);
    }

    $sql = "SHOW KEYS FROM $table WHERE Key_name = 'PRIMARY'";
    $primaryResult = $conn->query($sql);
    $primaryKeyArr = $primaryResult->fetch_array(MYSQLI_ASSOC);

    $content = genParentClass($table, $fields, $primaryKeyArr['Column_name']);

    $table = to_camel_case($table, true)."DaoBase";
    file_put_contents($target_folder.$table.".php", $content);
}

function genParentClass($table, $fields, $primaryKey) {
	global $base_class, $db_sche;
    $class = to_camel_case($table, true);

    $rv.= "abstract class $class extends $base_class {".PHP_EOL.PHP_EOL;

    $rv.= "    public static \$table = '$table';".PHP_EOL.PHP_EOL;

    $rv.= "    protected function init() {".PHP_EOL;
    $rv.= "        \$this->var['id'] = 0;".PHP_EOL;
    foreach ($fields as $field) {
    	if ($field=='id') { continue; }
        $rv.= "        \$this->var['$field'] = NULL;".PHP_EOL;
    }
    $rv.= PHP_EOL;
    foreach ($fields as $field) {
        $rv.= "        \$this->update['$field'] = false;".PHP_EOL;
    }
    $rv.= "    }".PHP_EOL.PHP_EOL;

    foreach ($fields as $field) {
        if ($field!=$primaryKey) {
            $setter = "set".to_camel_case($field, true)."(\$$field)";
            $rv.= "    public function $setter {".PHP_EOL;
            $rv.= "        if (\$this->var['$field'] !== \$$field) {".PHP_EOL;
            $rv.= "            \$this->var['$field'] = \$".$field.";".PHP_EOL;
            $rv.= "            \$this->update['$field'] = true;".PHP_EOL;
            $rv.= "        }".PHP_EOL;
            $rv.= "    }".PHP_EOL;
        }
        $getter = "get".to_camel_case($field, true)."()";
        $rv.= "    public function $getter {".PHP_EOL;
        $rv.= "        return \$this->var['$field'];".PHP_EOL;
        $rv.= "    }".PHP_EOL.PHP_EOL;
    }

    $rv.= "    public function getTableName() {".PHP_EOL;
    $rv.= "        return self::\$table;".PHP_EOL;
    $rv.= "    }".PHP_EOL.PHP_EOL;
    $rv.= "    protected function getIdColumnName() {".PHP_EOL;
    $rv.= "        return '$primaryKey';".PHP_EOL;
    $rv.= "    }".PHP_EOL;
    $rv.= "}";

    return $rv;
}

function to_camel_case($str, $capitalise_first_char = false) {
    if($capitalise_first_char) {
        $str[0] = strtoupper($str[0]);
    }
    $func = create_function('$c', 'return strtoupper($c[1]);');
    return preg_replace_callback('/_([a-z])/', $func, $str);
}
