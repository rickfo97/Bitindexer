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
        if (isset($_REQUEST['oc'])) {
            $order[0] = $_REQUEST['oc'];
            $order[1] = isset($_REQUEST['o']) ? $_REQUEST['o'] : 'DESC';
        }
        if (isset($_REQUEST['c']) && $_REQUEST['c'] > 0) {
            $torrents = TorrentModel::getTorrents($_REQUEST['c'], $order, $page);
            $pages = TorrentModel::torrentPages($torrents['query'], [':category' => $_REQUEST['c']]);
        } else {
            $torrents = TorrentModel::getTorrents(0, $order, $page);
            $pages = TorrentModel::torrentPages($torrents['query']);
        }
        unset($torrents['query']);
        $data = ['torrents' => $torrents, 'page' => ['current' => $page, 'max' => $pages]];
        return Route::$ajax ? json_encode($data) : View::render('torrent/browse', $data);
    }

    public function showPage($id)
    {
        $torrent = new TorrentModel();
        $torrent = $torrent->find($id);
        $scrape = Api::scrapeTracker($torrent->get()->info_hash);
        $torrent->set('seed', $scrape['files'][$torrent->get()->info_hash]['complete']);
        $torrent->set('leech', $scrape['files'][$torrent->get()->info_hash]['incomplete']);
        $torrent->save();
        return View::render('torrent/view', ['torrent' => $torrent->get()]);
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
            $torrentModel = new TorrentModel();
            $torrentModel
                ->set('name', $_POST['name'])
                ->set('info_hash', $torrent['info_hash'])
                ->set('description', strlen($_POST['description']) > 0 ? $_POST['description'] : '')
                ->set('magnet', TorrentModel::generateMagnet($torrent['info_hash'], $torrent['trackers']))
                ->set('total_size', $torrent['size'])
                ->set('category_id', $_POST['category'])
                ->set('user_id', $_SESSION['user_id'])
                ->create();

            if (isset($torrentModel->get()->id)) {
                $id = $torrentModel->get()->id;
                echo $id;
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