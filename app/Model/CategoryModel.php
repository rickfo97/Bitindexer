<?php
/**
 * Created by PhpStorm.
 * User: Rickardh
 * Date: 2017-03-27
 * Time: 18:56
 */

namespace App\Model;

use App\Core\Database;

class CategoryModel
{

    public static function getCategories(){
        $database = Database::getFactory()->getConnection();

        $stmt = $database->prepare("SELECT *FROM Category");
        $stmt->execute();
        return $stmt->fetchAll();
    }

}