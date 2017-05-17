<?php

namespace App\Core;

class Route
{

    private static $paths = array();
    public static $current = "";
    public static $ajax = false;

    private static function addPath($method, $uri, $callback)
    {
        self::$paths[] = array('method' => $method, 'uri' => $uri, 'callback' => $callback);
    }

    public static function get($uri, $callback)
    {
        self::addPath('GET', $uri, $callback);
    }

    public static function post($uri, $callback)
    {
        self::addPath('POST', $uri, $callback);
    }

    public static function delete($uri, $callback)
    {
        self::addPath('DELETE', $uri, $callback);
    }

    public static function put($uri, $callback)
    {
        self::addPath('PUT', $uri, $callback);
    }

    public static function patch($uri, $callback)
    {
        self::addPath('PATCH', $uri, $callback);
    }

    public static function run()
    {
        self::$current = isset($_REQUEST['uri']) ? $_REQUEST['uri'] : '';
        $method = isset($_REQUEST['method']) ? $_REQUEST['method'] : $_SERVER['REQUEST_METHOD'];
        if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            self::$ajax = true;
        }
        foreach (self::$paths as $path) {
            if ($path['method'] == $method) {
                $params = self::match($path['uri'], isset(self::$current) ? self::$current : $_SERVER['REQUEST_URI']);
                if ($params !== false) {
                    if (is_string($path['callback']) && strpos($path['callback'], '@') > 0) {
                        $objectInfo = explode('@', $path['callback']);
                        if (sizeof($objectInfo) === 2) {
                            $controller = '\\App\\Controller\\' . $objectInfo[0];
                            return call_user_func_array(array(new $controller, $objectInfo[1]), $params);
                        }
                    }
                    if (is_callable('\\App\\Controller\\' . $path['callback'])) {
                        return call_user_func_array('\\App\\Controller\\' . $path['callback'], $params);
                    }
                }
            }
        }
        return null;
    }

    private static function match($uri, $toMatch)
    {
        $uriParts = preg_split('@/@', $uri, NULL, PREG_SPLIT_NO_EMPTY);
        $matchParts = preg_split('@/@', $toMatch, NULL, PREG_SPLIT_NO_EMPTY);
        $params = array();
        if ((sizeof($uriParts) == sizeof($matchParts)) || (strpos($uri, '?') !== false)) {
            foreach ($uriParts as $key => $part) {
                if (strpos($part, '{') === false) {
                    if ($part != $matchParts[$key]) {
                        return false;
                    }
                } else if (strpos($part, '?') === false || isset($matchParts[$key])) {
                    $params[] = $matchParts[$key];
                }
            }
        } else {
            return false;
        }
        return $params;
    }
}