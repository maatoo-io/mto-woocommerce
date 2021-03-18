<?php


namespace Maatoo\WooCommerce\Service\LogErrors;

class LogData
{
    public static function writeApiErrors($data)
    {
        $file = './api-errors.txt';
       if(self::isLogFileWritable($file)){
           self::logFile($data, $file);
       }
    }

    public static function writeTechErrors($data)
    {
        $file = './technical-errors.txt';
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
}