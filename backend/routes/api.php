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
use App\Http\Controllers\Api\MatchEventsAdminController;
use App\Http\Controllers\Api\MatchLockController;
use App\Http\Controllers\Api\PublicStandingsController;
use App\Http\Controllers\Api\PublicLeagueStatsController;
use App\Http\Controllers\Api\AdminLeagueStatsController;
use App\Http\Controllers\Api\PublicLeagueDetailController;
use App\Http\Controllers\Api\AdminMatchReportController;
use App\Http\Controllers\Api\PublicMatchReportController;
use App\Http\Controllers\Api\SeasonController;
use App\Http\Controllers\Api\AdminCompetitionController;

Route::get('/ping', fn() => ['pong' => now()]);

// Auth pública
Route::post('/auth/register', RegisterController::class);
Route::post('/auth/login', [LoginController::class, 'store']);
Route::get('/regions', [RegionController::class, 'index']);
Route::get('/provinces', [ProvinceController::class, 'index']);
Route::get('/competitions', [CompetitionController::class, 'index']);
Route::get('/seasons', [SeasonController::class, 'index']);
Route::get('/teams/{team}/players', [TeamPlayersController::class, 'index']);

Route::get('/matches/{match}/report', [PublicMatchReportController::class, 'show']);

// Listar jornadas de una liga (opcional: ?group=ID|code)
Route::get('/leagues/{league}/matchdays', [PublicMatchdayController::class, 'index']);

// Listar partidos de una jornada
Route::get('/leagues/{league}/matchdays/{number}/matches', [PublicMatchController::class, 'byMatchday']);

Route::get('/leagues/{league}', [CompetitionController::class, 'show']);
Route::get('/leagues/{league}/groups', [CompetitionController::class, 'groups']);
Route::get('/leagues/{league}/siblings', [CompetitionController::class, 'siblings']);
Route::get('/matches/{match}', [PublicMatchDetailController::class, 'show']);
Route::get('/leagues/{league}/standings', [PublicStandingsController::class, 'show']);
Route::get('/leagues/{league}/standings', [PublicStandingsController::class, 'show']);
Route::get('/leagues/{league}/stats', [PublicLeagueStatsController::class, 'show']);
Route::get('/leagues/{league}/detail', [PublicLeagueDetailController::class, 'show']);
Route::get('/leagues/{league}/versions', [CompetitionController::class, 'versions']);

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

    Route::put(
        '/admin/matches/{match}/events',
        [MatchEventsAdminController::class, 'sync']
    );

    Route::post('/admin/matches/{match}/open',   [MatchLockController::class, 'open']);
    Route::post('/admin/matches/{match}/close',  [MatchLockController::class, 'close']);
    Route::post('/admin/matches/{match}/verify', [MatchLockController::class, 'verify']);

    Route::get(
        '/admin/leagues/{league}/stats/overrides',
        [AdminLeagueStatsController::class, 'index']
    );

    Route::put(
        '/admin/leagues/{league}/stats/overrides',
        [AdminLeagueStatsController::class, 'sync']
    );

    Route::post('/admin/matches/{match}/report', [\App\Http\Controllers\Api\AdminMatchReportController::class, 'upload']);
    Route::delete('/admin/matches/{match}/report', [\App\Http\Controllers\Api\AdminMatchReportController::class, 'destroy']);

    Route::prefix('admin')->middleware('role:superadmin,admin')->group(function () {
        Route::get('/competitions', [AdminCompetitionController::class, 'index']);
        Route::get('/competitions/{competition}/leagues', [AdminCompetitionController::class, 'leagues']);
    });
});
