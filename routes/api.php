<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [\App\Http\Controllers\AuthController::class, 'register']);
Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Watchlist Routes
    Route::get('/watchlist', [\App\Http\Controllers\WatchlistController::class, 'index']);
    Route::post('/watchlist', [\App\Http\Controllers\WatchlistController::class, 'store']);
    Route::delete('/watchlist/{tmdbId}', [\App\Http\Controllers\WatchlistController::class, 'destroy']);
    Route::patch('/watchlist/{tmdbId}', [\App\Http\Controllers\WatchlistController::class, 'update']);
});

// Media Routes (Movies & TV Shows)
Route::get('/media/search', [\App\Http\Controllers\MediaController::class, 'search']);
Route::get('/media/{type}/{id}', [\App\Http\Controllers\MediaController::class, 'show']);
Route::get('/tv/popular', [\App\Http\Controllers\MediaController::class, 'popularTv']);
