<?php

namespace App\Controller;

use App\Core\Api;
use App\Core\Bencode;
use App\Core\Config;
use App\Core\Redirect;
use App\Core\Session;
use App\Core\View;
use App\Core\Route;
use App\Model\TorrentModel;
use App\Model\CategoryModel;

class TorrentController
{
    public function __construct()
    {
        if (Config::get('private') && Session::getUser() == false){
            Redirect::to('login');
        }
    }

    public function browse($page = 1)
    {
        $torrents = [];
        $pages = 0;
        $order = [];
        if (isset($_REQUEST['order_column'])) {
            $order[0] = $_REQUEST['order_column'];
            $order[1] = isset($_REQUEST['order']) ? $_REQUEST['order'] : 'DESC';
        }
        if (isset($_REQUEST['category']) && $_REQUEST['category'] > 0) {
            $torrents = TorrentModel::getTorrents($_REQUEST['category'], $order, $page);
            $pages = TorrentModel::torrentPages($torrents['query'], [':category' => $_REQUEST['category']]);
        } else {
            $torrents = TorrentModel::getTorrents(0, $order, $page);
            $pages = TorrentModel::torrentPages($torrents['query']);
        }
        unset($torrents['query']);
        return View::render('torrent/browse', ['torrents' => $torrents, 'page' => ['current' => $page, 'max' => $pages]]);
    }

    public function showPage($id)
    {
        return View::render('torrent/view', ['torrent' => TorrentModel::getTorrent($id)]);
    }

    public function search($page = 1)
    {
        $torrents = TorrentModel::searchTorrent($page);
        return View::render('torrent/browse', ['torrents' => $torrents, 'page' => ['current' => $page, 'max' => isset($torrents[0]) ? $torrents[0]['max_pages'] : 0]]);
    }

    public function uploadPage()
    {
        return View::render('torrent/upload', ['categories' => CategoryModel::getCategories()]);
    }

    public function upload()
    {
        if (strlen($_POST['name']) > 255) {
            if (Route::$ajax) {
                return json_encode(['success' => false, 'error' => 'Torrent name to long']);
            }
            Redirect::to('upload');
        }
        if (isset($_FILES['torrentFile'])) {
            $torrent = TorrentModel::decodeTorrent($_FILES['torrentFile']['tmp_name']);

            $id = TorrentModel::addTorrent([
                'name' => $_POST['name'],
                'info_hash' => $torrent['info_hash'],
                'description' => $_POST['description'],
                'magnet' => TorrentModel::generateMagnet($torrent['info_hash'], $torrent['trackers']),
                'total_size' => $torrent['size'],
                'category' => $_POST['category']
            ]);

            if ($id !== false) {
                if (move_uploaded_file($_FILES['torrentFile']['tmp_name'], $_SERVER['DOCUMENT_ROOT'] . '/torrents/' . $id . '.torrent')) {
                    if (strlen(Config::get('tracker')) > 0){
                        Api::callTracker('torrent', 'add', ['info_hash' => $torrent['info_hash'], 'user_id' => Session::getUser()->torrent_pass]);
                    }
                    if (Route::$ajax) {
                        return json_encode(['success' => true]);
                    }
                    Redirect::to('torrent/' . $id);
                } else {
                    if (Route::$ajax) {
                        TorrentModel::removeTorrent($id);
                        return json_encode(['success' => false, 'error' => 'Failed when moving file']);
                    }
                    Redirect::to('upload');
                }
            } else {
                if (Route::$ajax) {
                    return json_encode(['success' => false, 'error' => 'Torrent upload failed']);
                }
                Redirect::to('upload');
            }

        } else {
            if (Route::$ajax) {
                return json_encode(['success' => false, 'error' => 'Torrent file missing']);
            }
            Redirect::to('upload');
        }
    }

    public function download($id)
    {
        $path = '../' . Config::get('torrent_path') . $id . '.torrent';
        if (!file_exists($path)){
            header("HTTP/1.0 404 Not Found");
            die();
        }

        $torrent = TorrentModel::getTorrent($id);

        if (!Config::get('private')){
            header("Content-type: application/x-bittorrent");
            header("Content-Disposition: filename=\"" . $torrent->name . ".torrent\"");
            header("Content-length: " . filesize($path));
            header("Cache-control: private");
            echo readfile($path);
            die();
        }
        $file = TorrentModel::decodeTorrent($path, true);
        $user = Session::getUser();

        $file['announce'] = preg_replace('/(.*){id}(.*)/i', '${1}' . $user->torrent_pass . '$2', $file['announce']);
        $encoded = Bencode::build($file);

        header("Content-type: application/x-bittorrent");
        header("Content-Disposition: filename=\"" . $torrent->name . ".torrent\"");
        header("Content-length: " . strlen($encoded));
        header("Cache-control: private");

        echo $encoded;
    }
}