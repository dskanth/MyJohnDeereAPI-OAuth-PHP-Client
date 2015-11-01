<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

/*Route::get('/', function()
{
	return View::make('hello');
});*/

//Route::get('/', 'HomeController@showWelcome');
//Route::get('/', 'OauthController@login');

Route::get('/', 'HomeController@showHome');
Route::get('/welcome', 'HomeController@showWelcome');
Route::get('/user_oauth/', 'UserController@login');
Route::get('/logout/', 'UserController@logout');

// File upload routes
Route::get('/create_file', 'OrgController@uploadFile');
Route::get('/file-upload', function() { return 'Invalid entry detected! Go to <a href="'.URL::to('/').'/create_file">Upload page</a>'; });
Route::post('/file-upload', 'OrgController@processUploadedFile');
Route::get('/list_files', 'OrgController@listFiles');
Route::get('/organizations/{id}/{name}/files', 'OrgController@organizationFiles')->where('id', '[\d+]+');
Route::get('/files/{id}/download', 'OrgController@fileDownload')->where('id', '[\d+]+');