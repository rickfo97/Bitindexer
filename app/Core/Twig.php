<?php

namespace App\Core;

use \App\Core\Text;
use \App\Core\Config;

class Twig
{

    private static $loader;
    private static $twig;

    public static function getTwig()
    {
        if (!isset(self::$twig)) {
            self::$loader = new \Twig_Loader_Filesystem(__DIR__ . '/../../template');
            self::$twig = new \Twig_Environment(self::$loader, array(
                'debug' => true,
                'cache' => __DIR__ . '/../../cache/twig'
            ));
            self::$twig->addFunction(new \Twig_Function('config', function ($key) {
                return Config::get($key);
            }));
            self::$twig->addFilter(new \Twig_Filter('format_bytes', '\App\Core\Text::formatBytes'));
        }
        return self::$twig;
    }

    public static function render($path, $context)
    {
        $twig = self::getTwig();
        $context['current_url'] = '/' . Route::$current;
        if (strpos($_SERVER['QUERY_STRING'], '&') !== false) {
            $context['url_query'] = '?' . substr($_SERVER['QUERY_STRING'], strpos($_SERVER['QUERY_STRING'], '&') + 1);
        }
        if (isset($_SESSION['user_id'])) {
            $context['user'] = \App\Model\UserModel::getUser($_SESSION['user_id']);
        }

        return $twig->render($path, $context);
    }
}