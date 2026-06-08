<?php
class APILogger
{
    private static $log_level = 5;

    public static function log($message, $level = 0, $additional = array()) {
        if ($level > self::$log_level) {
            return;
        }
        ob_start();
        echo date('d.m.Y H:i:s').' '.$message."\n";
        if (!empty($additional)) {
            print_r($additional);
            echo "\n";
        }
        $msg = ob_get_contents();
        ob_end_clean();
        echo $msg;
        /*
        $fp = fopen('yandex_log.log', 'a');
        fwrite($fp, $msg);
        fclose($fp);
        */
    }
}