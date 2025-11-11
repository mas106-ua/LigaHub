<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Api\RegionController;
use App\Http\Controllers\Api\CompetitionController;
use App\Http\Controllers\Api\GeoController;
use App\Http\Controllers\Api\ProvinceController;
use App\Models\Province;

Route::get('/ping', fn() => ['pong' => now()]);

// Auth pública
Route::post('/auth/register', RegisterController::class);
Route::post('/auth/login', [LoginController::class, 'store']);
Route::get('/regions', [RegionController::class, 'index']);
Route::get('/provinces', [ProvinceController::class, 'index']);
Route::get('/competitions', [CompetitionController::class, 'index']);

// Auth protegida
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn (Request $r) => $r->user());
    Route::post('/auth/logout', [LogoutController::class, 'destroy']);
    Route::get('/admin/dashboard', function () {
        return response()->json([
            'message' => 'Hola superadmin',
        ]);
    })->middleware('role:superadmin');

    Route::get('/admin/stats', function () {
        return response()->json([
            'message' => 'Stats visibles para superadmin y admin',
        ]);
    })->middleware('role:superadmin,admin');

    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/password', [ProfileController::class, 'updatePassword']);
});
