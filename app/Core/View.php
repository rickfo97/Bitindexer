<?php

namespace App\Core;

class View
{

    public static function render($path, $params = array())
    {
        if (Route::$ajax){
            return json_encode($params);
        }
        $base = __DIR__ . '/../../template/';
        if (file_exists($base . $path)){
            return Twig::render($path, $params);
        }
        if (file_exists($base . $path . '.twig')){
            return Twig::render($path . '.twig', $params);
        }
        if (file_exists($base . $path . '.php')){
            return Twig::render($path . '.php', $params);
        }
        if (file_exists($base . $path . '.html')){
            return Twig::render($path . '.html', $params);
        }
        return Twig::render('error/dev-404.twig', $params);
    }

}