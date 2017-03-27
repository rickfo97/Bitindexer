<?php

namespace App\Core;

class Redirect
{
    public static function to($path, $permanent = false)
    {
        if (headers_sent() === false) {
            header('Location: /' . $path, true, ($permanent === true) ? 301 : 302);
        }
        exit();
    }
}