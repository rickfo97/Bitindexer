<?php
/**
 * Created by PhpStorm.
 * User: Rickardh
 * Date: 2017-04-28
 * Time: 23:59
 */

namespace App\Core;


use App\Model\UserModel;

class Session
{
    public static function getUser()
    {
        if (!isset($_SESSION['user_id'])){
            return false;
        }
        $user = UserModel::getUser($_SESSION['user_id']);
        if ($user){
            return $user;
        }
        return false;
    }
}