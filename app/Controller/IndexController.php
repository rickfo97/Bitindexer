<?php

namespace App\Controller;

use App\Core\View;
use App\Core\Config;
use App\Core\Session;
use App\Core\Redirect;
use App\Model\TorrentModel;

class IndexController
{
    public function __construct()
    {
        if (Config::get('private') && Session::getUser() == false){
            Redirect::to('login');
        }
    }

    public function index()
    {
        return View::render('index', ['torrents' => TorrentModel::getRecent(5)]);
    }

    public static function about()
    {
        return View::render('info/about');
    }

    public function blog()
    {
        return View::render('info/blog');
    }
}