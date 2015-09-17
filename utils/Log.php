<?php
class Log {
    const APP = 1;
    const DB = 2;

    public static function info($message, $file=null) {
        global $log_level;
        if ($log_level>3) { Log::log($message, 4, $file); }
    }

    public static function warn($message, $file=null) {
        global $log_level;
        if ($log_level>2) { Log::log($message, 3, $file); }
    }

    public static function error($message, $file=null) {
        global $log_level;
        if ($log_level>1) { Log::log($message, 2, $file); }
    }

    public static function fatal($message, $file=null) {
        global $log_level;
        if ($log_level>0) { Log::log($message, 1, $file); }
    }

    private static function log($message, $level, $file=Log.APP) {
        global $app_log, $db_log;

        if ($file==Log.APP) { $file = $log_file; }
        else if ($file==Log.DB) { $file = $db_log; }

        if ($level==4) { $slevel = "INFO - "; }
        else if ($level==3) { $slevel = "WARN - "; }
        else if ($level==2) { $slevel = "ERROR - "; }
        else if ($level==1) { $slevel = "FATAL - "; }

        $log = fopen($file, 'a');
        if ($log!=FALSE) {
            fwrite($log, '['.date('Y-m-d H:i:s').'] '.$slevel.$message.PHP_EOL);
            fclose($log);
        } else {
            error_log('['.date('Y-m-d H:i:s').'] '.$slevel.$message.PHP_EOL);
        }
    }

    public static function access($message) {
        global $access_log;

        if (isset($access_log) && !empty($access_log))    {
            $body = '{  "IP":"'.Utility::getClientIp().
            '", "REQUEST":"'.$message.'  }';
            $access = fopen($access_log, 'a');
            if ($access!=FALSE) {
                fwrite($access, '['.date('Y-m-d H:i:s').'] '.$body.PHP_EOL.PHP_EOL);
                fclose($access);
            } else {
                error_log('['.date('Y-m-d H:i:s').'] '.$slevel.$message.PHP_EOL);
            }
        }
        else {
            Log::warn('No access log file provided ...');
        }
    }
}