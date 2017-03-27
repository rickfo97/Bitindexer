<?php

namespace App\Controller;

use App\Core\Route;
use App\Core\View;
use App\Core\Redirect;
use App\Model\UserModel;

class UserController
{

    public function loginPage()
    {
        return View::render('user/login.twig');
    }

    public function registerPage()
    {
        return View::render('user/register.twig');
    }

    public function applyPage()
    {
        return View::render('user/apply.twig');
    }

    public function profilePage()
    {
        return View::render('user/profile.twig');
    }

    public function editPage()
    {
        return View::render('user/edit.twig');
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

    public function login()
    {
        $email = $_POST['email'];
        $password = $_POST['password'];
        $user = UserModel::userLogin($email, $password);
        if (!is_array($user) && $user !== false) {
            $_SESSION['user_id'] = $user;
            print_r($_SESSION);
            Redirect::to('browse');
        }
        if (Route::$ajax){
            return json_encode($user);
        }
        Redirect::to('login');
    }

    public function register()
    {
        $data = array(
            'username' => $_POST['username'],
            'email' => $_POST['email'],
            'password' => $_POST['password'],
            'verify_password' => $_POST['verify_password']
        );
        if ($data['password'] !== $data['verify_password'])
            return json_encode(['success' => false, 'error' => 'Password doesn\'t match']);
        $user = UserModel::registerUser($data['username'], $data['email'], $data['password']);
        if ($user === true) {
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

    //TODO Apply to become a member of site
    public function apply()
    {

    }
}