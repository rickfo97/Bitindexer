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
        self::$lastMessage = $parsed->message;
        if ($parsed->message == "Success"){
            return self::$lastMessage;
        }else{
            return false;
        }
    }

    public static function scrapeTracker($info_hash = ''){
        $url = Config::get('tracker');
        $data['auth_token'] = Config::get('tracker_key');
        $data['info_hash'] = $info_hash;
        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data)
            )
        );
        $context  = stream_context_create($options);
        $result = file_get_contents($url . '/scrape', false, $context);
        if ($result === FALSE) {
            return false;
        }
        $parsed = Bencode::decode($result);
        self::$lastMessage = $parsed;
        if ($parsed){
            return self::$lastMessage;
        }else{
            return false;
        }
    }

    public static function getLastMessage()
    {
        return self::$lastMessage;
    }
}