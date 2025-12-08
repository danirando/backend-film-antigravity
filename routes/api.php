<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/movies/search', [\App\Http\Controllers\MovieController::class, 'search']);
Route::get('/movies/{id}', [\App\Http\Controllers\MovieController::class, 'show']);
