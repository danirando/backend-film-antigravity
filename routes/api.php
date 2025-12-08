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

Route::get('/movies/search', [\App\Http\Controllers\MovieController::class, 'search']);
Route::get('/movies/{id}', [\App\Http\Controllers\MovieController::class, 'show']);
