<?php

namespace App\Core;

class Config
{

    private static $config = array(
        'db_driver' => 'mysql',
        'db_host' => 'localhost',
        'db_name' => 'indexer',
        'db_user' => 'root',
        'db_password' => '',

        'private_indexer' => false,

        'password_algorithm' => PASSWORD_BCRYPT,
        'password_cost' => 10,
        'remember_lifetime' => (7 * 24 * 60 * 60),
    );

    public static function get($key)
    {
        if (key_exists($key, self::$config)) {
            return self::$config[$key];
        }
        return null;
    }
}