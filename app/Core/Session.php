<?php

namespace App\Core;


use App\Model\UserModel;

class Session
{

    private static $userid;

    public static function getUser()
    {
        if (!isset($_SESSION['user_id']) && !isset($_COOKIE['session_id'])){
            return false;
        }
        $id = isset($_COOKIE['session_id']) ? $_COOKIE['session_id'] : $_SESSION['user_id'];
        $user = UserModel::getUser($_SESSION['user_id']);
        if ($user){
            return $user;
        }
        return false;
    }
}