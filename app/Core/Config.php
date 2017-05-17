<?php

namespace App\Core;

class Config
{

    private static $config;
    private static $configFile;
    private static $path = '../';

    public static function get($key)
    {
        if(is_null(self::$config)){
            self::$configFile = file_exists(self::$path . 'config.ini.php') ?  (self::$path . 'config.ini.php') : (self::$path . 'config.example.ini.php');
            self::$config = parse_ini_file(self::$configFile, false, INI_SCANNER_TYPED);
        }
        if (key_exists($key, self::$config)) {
            return self::$config[$key];
        }
    }

    public static function change($key, $value)
    {
        if(is_null(self::$config)){
            self::$configFile = file_exists(self::$path . 'config.ini.php') ?  (self::$path . 'config.ini.php') : (self::$path . 'config.example.ini.php');
            self::$config = parse_ini_file(self::$configFile, false, INI_SCANNER_TYPED);
        }
        if (key_exists($key, self::$config)) {
            self::$config[$key] = $value;
            return true;
        }
        return false;
    }

    public static function save()
    {
        $file = file_get_contents(self::$configFile);
        foreach (self::$config as $key => $value){
            $file = preg_replace("/$key=.*/", "$key=" . var_export($value, true), $file, 1);
        }
        return file_put_contents(self::$configFile, $file, LOCK_EX);
    }
}
