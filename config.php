<?php
$env_name = 'local';
$base_host = 'https://local.api.confone.com';


//=======================================================================================
// auto include directory information
$autoload_dirs = array( 
    'dal',
    'dal/base',
    'dal/connector',
    'dal/dao',
    'dal/cache',
    'interface/ajax',
    'interface/page',
    'interface/rest',
    'utils'
);


//=======================================================================================
// Mysql database information
$db_host = '127.0.0.1';
//$db_host = 'dbdev.indochino.com';
$db_port = '3306';
$db_name = 'confone';
$db_username = 'confone';
$db_password = 'confonepass';


//=======================================================================================
// Cache server information
$cache_servers = array (
'cache.indochino.com' => '11211'
);


//=======================================================================================
// logging information
// INFO=4, WARN=3, ERROR=2, FATAL=1, NOTHING=0
$log_level = 4;
$log_file = '/var/log/confone/application.log';
$db_log = '/var/log/confone/database.log';

$access_on = 1;
$access_log = '/var/www/payment/log/rest/access.log';


//=======================================================================================
// signature path
$signature_path = '/var/www/payment/signature/';