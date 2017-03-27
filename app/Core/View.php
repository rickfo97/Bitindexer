<?php

namespace App\Core;

class View
{

    public static function render($path, $params = array())
    {
        return Twig::render($path, $params);
    }

}