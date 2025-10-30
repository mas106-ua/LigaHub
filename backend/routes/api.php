<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;

Route::get('/ping', fn() => ['pong' => now()]);

// Auth pública
Route::post('/auth/register', RegisterController::class);
Route::post('/auth/login', [LoginController::class, 'store']);

// Auth protegida
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn (Request $r) => $r->user());
    Route::post('/auth/logout', [LogoutController::class, 'destroy']);
});
