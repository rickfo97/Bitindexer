<?php

namespace App\Model;

use App\Core\Database;
use App\Core\Config;
use App\Core\Model;
use App\Core\Text;

/**
 * Class UserModel
 * @package App\Model
 */
class UserModel extends Model
{

    /**
     * @param $user_id
     * @return mixed|null
     */
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

    /**
     * @param $username
     * @return mixed|null
     */
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

    /**
     * @param $email
     * @return mixed|null
     */
    public static function getUserByEmail($email)
    {
        $dbc = Database::getFactory()->getConnection();
        $stmt = $dbc->prepare("SELECT username, email, torrent_pass FROM User WHERE email = :email LIMIT 1");
        $stmt->execute(array(':email' => $email));
        if ($user = $stmt->fetchObject()) {
            return $user;
        }
        return null;
    }

    /**
     * @param $login
     * @param $password
     * @return array|bool
     */
    public static function userLogin($login, $password)
    {
        $dbc = Database::getFactory()->getConnection();
        $userStmt = $dbc->prepare("SELECT id, password FROM User WHERE username = :login OR email = :login LIMIT 1");
        $userStmt->execute(array(':login' => $login));
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
        $id = Text::random_str(8);
        //TODO Make sure id is free
        $success = $stmt->execute(array(
            ':id' => $id,
            ':username' => $username,
            ':email' => $email,
            ':password' => $password_hash,
            ':torrent_pass' => Text::random_str(40)
        ));
        if ($success) {
            return self::getUser($id);
        }
        return false;
    }


    /**
     * @param $email : string for email
     */
    public static function applyUser($country, $email, $reason, $offer)
    {
        $dbc = Database::getFactory()->getConnection();
        $stmt = $dbc->prepare("INSERT INTO Application(country, email, reason, offer) VALUES (:country, :email, :reason, :offer)");
        $stmt->execute([
            ':country' => $country,
            ':email' => $email,
            ':reason' => $reason,
            ':offer' => $offer
        ]);
        if ($stmt->rowCount() == 1){
            return true;
        }
        return false;
    }
}