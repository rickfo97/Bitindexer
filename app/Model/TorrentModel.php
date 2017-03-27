<?php

namespace App\Model;

use App\Core\Database;
use App\Core\Bencode;

class TorrentModel
{

    public static function getTorrent($id)
    {
        $dbc = Database::getFactory()->getConnection();
        $stmt = $dbc->prepare("SELECT Torrent.id, info_hash, name, description, path, magnet FROM Torrent WHERE id = :id");
        $stmt->execute(array(':id' => $id));
        if ($torrent = $stmt->fetchObject()) {
            return $torrent;
        }
        return false;
    }

    public static function getTorrents($category = 0, $page = 1, $limit = 25)
    {
        if ($page <= 0) {
            $page = 1;
        }
        $page = (($page - 1) * $limit);
        $dbc = Database::getFactory()->getConnection();
        $stmt = $dbc->prepare("SELECT Torrent.id, info_hash, Torrent.name, description, magnet, date_added as added, seed, leech, Torrent.total_size as size, Category.id as category_id, Category.name as category_name, User.username FROM Torrent LEFT JOIN User ON Torrent.user_id = User.id LEFT JOIN Category ON Torrent.category_id = Category.id " . ($category > 0 ? "WHERE Category.id = :category OR Category.parent_id = :category" : '') . " GROUP BY Torrent.id LIMIT $page, $limit");
        $parameters = [];
        if ($category > 0){
            $parameters[':category'] = $category;
        }
        $stmt->execute($parameters);
        $torrents = $stmt->fetchAll();
        $torrents['query'] = $stmt->queryString;
        return $torrents;
    }

    public static function addTorrent($torrent)
    {
        $dbc = Database::getFactory()->getConnection();
        $stmt = $dbc->prepare("INSERT INTO Torrent(id, info_hash, user_id, name, description, path, magnet, total_size, category_id) VALUES(:id, :info_hash, :user_id, :name, :description, :path, :magnet, :total, :category_id)");
        //TODO Check if free
        $id = substr(uniqid(), 0, 12);
        $success = $stmt->execute(array(
            ':id' => $id,
            ':info_hash' => $torrent['info_hash'],
            ':user_id' => $_SESSION['user_id'],
            ':name' => $torrent['name'],
            ':description' => $torrent['description'],
            ':path' => 'torrents/' . $id . '.torrent',
            ':total' => $torrent['total_size'],
            ':magnet' => $torrent['magnet'],
            ':category_id' => $torrent['category']
        ));
        print_r($stmt->errorInfo());
        if ($success) {
            return $id;
        }
        return false;
    }

    public static function removeTorrent($id){
        $dbc = Database::getFactory()->getConnection();
        $stmt = $dbc->prepare("DELETE FROM Torrent WHERE id = :id");
        return $stmt->execute([
            ':id' => $id
        ]);

    }

    public static function torrentPages($query, $parameters = [], $limit = 25)
    {
        $dbc = Database::getFactory()->getConnection();
        $sql = preg_replace("/.*FROM(.*)LIMIT.*/i", 'SELECT (COUNT(*) / ' . $limit . ') as pages FROM $1', $query);
        $stmt = $dbc->prepare($sql);
        $stmt->execute($parameters);
        return ceil($stmt->fetchObject()->pages);
    }

    public static function getRecent($limit = 5)
    {
        $dbc = Database::getFactory()->getConnection();
        $stmt = $dbc->prepare("SELECT id, info_hash, name, description, date_added FROM Torrent ORDER BY date_added DESC LIMIT $limit");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    //TODO search based on name maybe description. Add to search terms.
    public static function searchTorrent($page = 1, $limit = 25)
    {
        if ($page <= 0) {
            $page = 1;
        }
        $page = (($page - 1) * $limit);
        $dbc = Database::getFactory()->getConnection();
    }

    public static function decodeTorrent($filePath){
        $handle = fopen($filePath, "r");
        $torrentFile = Bencode::decode(fread($handle, filesize($filePath)));
        fclose($handle);

        $torrent = ['name' => $torrentFile['info']['name'], 'info_hash' => sha1(Bencode::build($torrentFile['info']), true), 'trackers' => []];
        $size = 0;
        if(isset($torrentFile['info']['files'])){
            foreach ($torrentFile['info']['files'] as $file){
                $size += $file['length'];
            }
        }else{
            $size = $torrentFile['info']['length'];
        }
        $torrent['size'] = self::formatBytes($size);

        $torrent['trackers'][] = $torrentFile['announce'];
        if (isset($torrentFile['announce-list'])){
            foreach ($torrentFile['announce-list'] as $tracker){
                $torrent['trackers'][] = $tracker;
            }
        }

        return $torrent;
    }

    //TODO Generate based on file that was uploaded
    public static function generateMagnet($info_hash, $trackers){
        $magnetLink = 'magnet:?xt=urn:btih:' . bin2hex($info_hash);
        foreach ($trackers as $tracker){
            if(is_string($tracker)){
                $magnetLink .= '&tr=' . $tracker;
                continue;
            }
            $magnetLink .= '&tr=' . $tracker[0];
        }
        return $magnetLink;
    }

    private static function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        // Uncomment one of the following alternatives
        // $bytes /= pow(1024, $pow);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}