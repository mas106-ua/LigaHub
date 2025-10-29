<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/ping', fn() => ['pong' => now()]);

Route::middleware('auth:sanctum')->get('/auth/me', function (Request $r) {
    return $r->user();
});

