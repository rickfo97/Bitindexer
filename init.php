<?php

require __DIR__ . '/vendor/autoload.php';

$whoops = new \Whoops\Run;
$whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
$whoops->register();

$options = array();
if (isset($_POST['remember']))
    $options['cookie_lifetime'] = \App\Core\Config::get('remember_lifetime');

session_start($options);

// Load in Routes
include_once 'app/Route/Routes.php';

return \App\Core\Route::run();