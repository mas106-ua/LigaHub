<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;

Route::get('/ping', fn() => ['pong' => now()]);

Route::middleware('auth:sanctum')->get('/auth/me', function (Request $r) {
    return $r->user();
});

Route::post('/auth/register', RegisterController::class);