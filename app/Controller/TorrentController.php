<?php

namespace App\Controller;

use App\Core\Redirect;
use App\Core\View;
use App\Core\Route;
use App\Model\TorrentModel;
use App\Model\CategoryModel;

class TorrentController
{
    public function browse($page = 1)
    {
        return View::render('torrent/browse.twig', ['torrents' => TorrentModel::getTorrents($page), 'page' => ['current' => $page, 'max' => TorrentModel::torrentPages()]]);
    }

    public function showPage($id)
    {
        return View::render('torrent/view.twig', ['torrent' => TorrentModel::getTorrent($id)]);
    }

    public function search($page = 1)
    {
        $torrents = TorrentModel::searchTorrent($page);
        return View::render('torrent/browse.twig', ['torrents' => $torrents, 'page' => ['current' => $page, 'max' => isset($torrents[0]) ? $torrents[0]['max_pages'] : 0]]);
    }

    public function uploadPage()
    {
        return View::render('torrent/upload.twig', ['categories' => CategoryModel::getCategories()]);
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
                    if (Route::$ajax) {
                        return json_encode(['success' => true]);
                    }
                    Redirect::to('torrent/' . $id);
                } else {
                    if (Route::$ajax) {
                        TorrentModel::removeTorrent($id);
                        return json_encode(['success' => false, 'error' => 'Failed when moving file']);
                    }
                    //Redirect::to('upload');
                }
            } else {
                if (Route::$ajax) {
                    return json_encode(['success' => false, 'error' => 'Torrent upload failed']);
                }
                //Redirect::to('upload');
            }

        } else {
            if (Route::$ajax) {
                return json_encode(['success' => false, 'error' => 'Torrent file missing']);
            }
            //Redirect::to('upload');
        }
    }
}