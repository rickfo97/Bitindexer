<?php

namespace App\Model;

use App\Core\Database;
use App\Core\Config;

class UserModel
{

    public static function getUser($user_id)
    {
        $dbc = Database::getFactory()->getConnection();
        $stmt = $dbc->prepare("SELECT username, email, torrent_pass FROM User WHERE id = :user_id LIMIT 1");
        $stmt->execute(array(':user_id' => $user_id));
        if ($user = $stmt->fetchObject()) {
            return $user;
        }
        return null;
    }

    public static function getUserByUsername($username)
    {
        $dbc = Database::getFactory()->getConnection();
        $stmt = $dbc->prepare("SELECT username, email, torrent_pass FROM User WHERE username = :username LIMIT 1");
        $stmt->execute(array(':username' => $username));
        if ($user = $stmt->fetchObject()) {
            return $user;
        }
        return null;
    }

    public static function userLogin($email, $password)
    {
        $dbc = Database::getFactory()->getConnection();
        $userStmt = $dbc->prepare("SELECT id, password FROM User WHERE email = :email LIMIT 1");
        $userStmt->execute(array(':email' => $email));
        if ($user = $userStmt->fetchObject()) {
            if (password_verify($password, $user->password)) {
                return $user->id;
            }
            return false;
        }
        return ['error' => $userStmt->errorInfo()];
    }

    /**
     * @param $username : string for username wish
     * @param $password : password string
     * @param $email : string for email
     * @return true on success else errorInfo
     */
    public static function registerUser($username, $email, $password)
    {
        $password_hash = password_hash($password, Config::get('password_algorithm'), ['cost' => Config::get('password_cost')]);
        $dbc = Database::getFactory()->getConnection();
        $stmt = $dbc->prepare("INSERT INTO User(id, username, email, password, torrent_pass) VALUES(:id, :username, :email, :password, :torrent_pass)");
        //TODO Make sure id is free
        $success = $stmt->execute(array(
            ':id' => substr(uniqid(), 0, 8),
            ':username' => $username,
            ':email' => $email,
            ':password' => $password_hash,
            ':torrent_pass' => (uniqid("", true) . uniqid())
        ));
        if ($success) {
            return true;
        }
        return $stmt->errorInfo();
    }


    /**
     * @param $username : string for username wish
     * @param $password : password string
     * @param $email : string for email
     * @param $applyID : int for apply table row
     */
    public static function applyUser($username, $password, $email, $applyID)
    {

    }
}