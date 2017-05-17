<?php

namespace App\Controller;

use App\Core\Api;
use App\Core\Config;
use App\Core\Route;
use App\Core\Session;
use App\Core\View;
use App\Core\Redirect;
use App\Model\UserModel;

class UserController
{

    public function __construct()
    {
        if (Config::get('private') && Session::getUser() == false){
            Redirect::to('login');
        }
    }

    public static function loginPage()
    {
        echo 'login';
        if (Config::get('private') && Session::getUser() == false){
            return View::render('login');
        }
        return View::render('user/login');
    }

    public static function registerPage()
    {
        if (Config::get('register')) {
            return View::render('user/register');
        }
        if (Config::get('apply')){
            Redirect::to('apply');
        }
    }

    public static function applyPage()
    {
        if (Config::get('apply')){
            return View::render('user/apply');
        }
        if (Config::get('register')) {
            Redirect::to('register');
        }
    }

    public function profilePage()
    {
        return View::render('user/profile');
    }

    public function editPage()
    {
        return View::render('user/edit');
    }

    public function logout()
    {
        unset($_SESSION);
        session_destroy();
        Redirect::to('');
    }

    public function userPage($id)
    {

    }

    public static function login()
    {
        $email = $_POST['email'];
        $password = $_POST['password'];
        $user = UserModel::userLogin($email, $password);
        if (!is_array($user) && $user !== false) {
            $_SESSION['user_id'] = $user;
            print_r($_SESSION);
            Redirect::to('');
        }
        if (Route::$ajax){
            return json_encode($user);
        }
        Redirect::to('login');
    }

    public static function register()
    {
        if(!Config::get('register')){
            Redirect::to('login');
        }
        $data = array(
            'username' => $_POST['username'],
            'email' => $_POST['email'],
            'password' => $_POST['password'],
            'verify_password' => $_POST['verify_password']
        );
        if ($data['password'] !== $data['verify_password'])
            return json_encode(['success' => false, 'error' => 'Password doesn\'t match']);
        $user = UserModel::registerUser($data['username'], $data['email'], $data['password']);
        if ($user) {
            Api::callTracker('user', 'add', ['torrent_pass' => $user->torrent_pass]);
            if(Route::$ajax){
                return json_encode(['success' => true]);
            }
            Redirect::to('login');
        } else {
            if(Route::$ajax) {
                return json_encode(['success' => false, 'error' => $user]);
            }
            Redirect::to('register');
        }
    }

    public function test()
    {
        return View::render('admin/dashboard');
    }

    //TODO Apply to become a member of site
    public static function apply()
    {
        if (!Config::get('apply')){
            Redirect::to('login');
        }
        if (UserModel::getUserByEmail($_POST['email'])){
            Redirect::to('apply');
        }
        //TODO Fetch and validate
        if (UserModel::applyUser($_POST['country'], $_POST['email'], $_POST['reason'], $_POST['offer'])){
            Redirect::to('apply/success');
        }
        Redirect::to('apply');
    }

    public static function siteLogin()
    {
        return View::render('login');
    }
}