<?php

namespace App\Controller;

use App\Core\View;
use App\Core\Bencode;
use App\Model\TorrentModel;

class IndexController
{
    public function index()
    {
        return View::render('index.twig', ['torrents' => TorrentModel::getRecent(5)]);
    }

    public function about()
    {
        return View::render('info/about.twig');
    }

    public function blog()
    {
        return View::render('info/blog.twig');
    }
}