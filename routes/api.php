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
    \Illuminate\Support\Facades\Cache::forget(\App\Http\Controllers\AdventOfCodeController::KEY);
    return response()->json([
        'frames' => [
            ['text' => 'REFRESHED'],
        ],
    ]);
});

Route::get('lcwc-911', \App\Http\Controllers\LCWC911Controller::class);

//Route::get('/github-pr-count', \App\Http\Controllers\GitHubPRCountController::class);
