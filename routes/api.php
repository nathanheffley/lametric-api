<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/advent-of-code', \App\Http\Controllers\AdventOfCodeController::class);
Route::get('/advent-of-code/refresh', function (\Illuminate\Http\Request $request) {
    \Illuminate\Support\Facades\Log::debug('get', $request->toArray());
});
Route::post('/advent-of-code/refresh', function (\Illuminate\Http\Request $request) {
    \Illuminate\Support\Facades\Log::debug('post', $request->toArray());
});

Route::get('lcwc-911', \App\Http\Controllers\LCWC911Controller::class);

//Route::get('/github-pr-count', \App\Http\Controllers\GitHubPRCountController::class);
