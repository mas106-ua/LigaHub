<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Api\RegionController;
use App\Http\Controllers\Api\CompetitionController;
use App\Http\Controllers\Api\ProvinceController;
use App\Http\Controllers\Api\PublicMatchdayController;
use App\Http\Controllers\Api\PublicMatchController;
use App\Http\Controllers\Api\PublicMatchDetailController;
use App\Http\Controllers\Api\AdminMatchResultController;
use App\Http\Controllers\Api\AdminMatchLineupController;
use App\Http\Controllers\Api\TeamPlayersController;

Route::get('/ping', fn() => ['pong' => now()]);

// Auth pública
Route::post('/auth/register', RegisterController::class);
Route::post('/auth/login', [LoginController::class, 'store']);
Route::get('/regions', [RegionController::class, 'index']);
Route::get('/provinces', [ProvinceController::class, 'index']);
Route::get('/competitions', [CompetitionController::class, 'index']);
Route::get('/teams/{team}/players', [TeamPlayersController::class, 'index']);

// Listar jornadas de una liga (opcional: ?group=ID|code)
Route::get('/leagues/{league}/matchdays', [PublicMatchdayController::class, 'index']);

// Listar partidos de una jornada
Route::get('/leagues/{league}/matchdays/{number}/matches', [PublicMatchController::class, 'byMatchday']);

Route::get('/leagues/{league}', [CompetitionController::class, 'show']);
Route::get('/leagues/{league}/groups', [CompetitionController::class, 'groups']);
Route::get('/leagues/{league}/siblings', [CompetitionController::class, 'siblings']);
Route::get('/matches/{match}', [PublicMatchDetailController::class, 'show']);

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

    Route::put(
        '/admin/leagues/{league}/matchdays/{number}/results',
        [AdminMatchResultController::class, 'bulkUpdate']
    );
    // Alineaciones de partido (admin)
    Route::put('/admin/matches/{match}/lineups', [AdminMatchLineupController::class, 'update']);
});
