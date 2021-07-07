<?php


namespace Maatoo\WooCommerce\Service\LogErrors;

class LogData
{
    public static function writeApiErrors($data)
    {
        $file = plugin_dir_path(__FILE__) . 'api-errors.txt';
       if(self::isLogFileWritable($file)){
           self::logFile($data, $file);
       }
    }

    public static function writeTechErrors($data)
    {
        $file = plugin_dir_path(__FILE__) . 'technical-errors.txt';
        if(self::isLogFileWritable($file)){
            self::logFile($data, $file);
        }
    }

    public static function writeDebug($data)
    {
        $file = plugin_dir_path(__FILE__) . 'debug.txt';
        if(self::isLogFileWritable($file)){
            self::logFile($data, $file);
        }
    }

    private static function logFile($textLog, $file)
    {
        $text = date('Y-m-d H:i:s') . PHP_EOL;
        $text .= $textLog;
        $text .= PHP_EOL;
        $f = file_put_contents($file, $text, FILE_APPEND | LOCK_EX);
        if (!$f) {
            echo 'Wrong open log-file.';
        }
    }

    private static function isLogFileWritable($file)
    {
        clearstatcache();
        if (!file_exists($file)) {
            $f = file_put_contents($file, '');
            if (!$f) {
                return sprintf('File %s does\'n exist and can\'t create it automatically', $file);
            }
        }

        if(is_writable($file)){
            return sprintf('File %s is not writable', $file);
        }

        return true;
    }

    public static function clearLogFiles(){
        $fileApi = plugin_dir_path(__FILE__) . 'api-errors.txt';
        $fileTech = plugin_dir_path(__FILE__) . 'technical-errors.txt';

        if(self::isLogFileWritable($fileApi)){
            file_put_contents($fileApi, '');
        }

        if(self::isLogFileWritable($fileTech)){
            file_put_contents($fileTech, '');
        }
    }

    public static function downloadLogLinks(){
        $html = '<div class="download-logs">';
        $fileApi = plugin_dir_url(__FILE__) . 'api-errors.txt';
        $fileTech = plugin_dir_url(__FILE__) . 'technical-errors.txt';
        $tech = file_get_contents($fileTech);
        if(!empty($tech)){
            $label = __('Failed  plugin tasks', 'mto-woocommerce');
            $html .= "<a href='{$fileTech}' download/>{$label}</a>";
        }
        $api = file_get_contents($fileApi);
        if(!empty($api)){
            $label = __('Failed API requests', 'mto-woocommerce');
            $html .= "<a href='{$fileApi}' download/>{$label}</a>";
        }
        return $html . '</div>';
    }
}