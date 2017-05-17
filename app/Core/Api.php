<?php

namespace App\Core;

use \App\Core\Config;

class Api
{
    private static $lastMessage = "";

    public static function callTracker($action, $method, $data = []){
        $url = Config::get('tracker');
        $data['auth_token'] = Config::get('tracker_key');
        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data)
            )
        );
        $context  = stream_context_create($options);
        $result = file_get_contents($url . '/api/' . $action . '/' . $method, false, $context);
        if ($result === FALSE) {
            return false;
        }
        $parsed = json_decode($result);
        if ($parsed->message == "Success"){
            return true;
        }else{
            self::$lastMessage = $parsed->message;
            return false;
        }
    }

    public static function getLastMessage()
    {
        return self::$lastMessage;
    }
}