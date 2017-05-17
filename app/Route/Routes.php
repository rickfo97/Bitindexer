<?php
namespace App\Route;

use App\Core\Redirect;
use App\Core\Route;

//Public paths
Route::get('/', 'IndexController@index');
Route::get('browse/{page?}', 'TorrentController@browse');
Route::get('search', 'TorrentController@search');
Route::get('about', 'IndexController@about');

//User paths
Route::get('login', 'UserController::loginPage');
Route::get('register', 'UserController::registerPage');
Route::get('apply', 'UserController::applyPage');
Route::get('profile', 'UserController@profilePage');
Route::get('profile/edit', 'UserController@editPage');
Route::get('logout', 'UserController@logout');

Route::get('admin/test', 'UserController@test');

Route::get('user/{id}', 'UserController@userPage');

Route::post('login', 'UserController::login');
Route::post('register', 'UserController::register');
Route::post('apply', 'UserController::apply');

//Torrent paths
Route::get('torrent/{id}', 'TorrentController@showPage');
Route::get('download/{id}', 'TorrentController@download');
Route::get('upload', 'TorrentController@uploadPage');
Route::post('upload', 'TorrentController@upload');
Route::patch('torrent/{id}', 'TorrentController@update');

//Redirects
Route::get('torrent', function (){
    Redirect::to('browse');
});