<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;

use App\Http\Controllers\ProfileController;

use App\Http\Controllers\Api\RegionController;
use App\Http\Controllers\Api\ProvinceController;
use App\Http\Controllers\Api\SeasonController;

use App\Http\Controllers\Api\CompetitionController;

use App\Http\Controllers\Api\PublicMatchdayController;
use App\Http\Controllers\Api\PublicMatchController;
use App\Http\Controllers\Api\PublicMatchDetailController;
use App\Http\Controllers\Api\PublicStandingsController;
use App\Http\Controllers\Api\PublicLeagueStatsController;
use App\Http\Controllers\Api\PublicLeagueDetailController;
use App\Http\Controllers\Api\PublicMatchReportController;

use App\Http\Controllers\Api\TeamPlayersController;

use App\Http\Controllers\Api\AdminMatchResultController;
use App\Http\Controllers\Api\AdminMatchLineupController;
use App\Http\Controllers\Api\MatchEventsAdminController;
use App\Http\Controllers\Api\MatchLockController;
use App\Http\Controllers\Api\AdminLeagueStatsController;
use App\Http\Controllers\Api\AdminMatchReportController;

use App\Http\Controllers\Api\AdminCompetitionController;
use App\Http\Controllers\Api\PrivateLeaguesController;
use App\Http\Controllers\Api\MyLeaguesController;
use App\Http\Controllers\Api\PrivateLeagueDetailController;
use App\Http\Controllers\Api\PrivateLeagueTeamsController;
use App\Http\Controllers\Api\PrivateLeaguePlayersController;

use App\Http\Controllers\Api\PrivateLeagueScheduleController;
use App\Http\Controllers\Api\PrivateMatchdayController;
use App\Http\Controllers\Api\PrivateMatchController;
use App\Http\Controllers\Api\PrivateMatchResultController;
use App\Http\Controllers\Api\PrivateLeagueStandingsController;
use App\Http\Controllers\Api\PrivateLeagueStatsController;
use App\Http\Controllers\Api\PrivateMatchReportController;

Route::get('/ping', fn () => ['pong' => now()]);

/*
|--------------------------------------------------------------------------
| Auth pública
|--------------------------------------------------------------------------
*/
Route::post('/auth/register', RegisterController::class);
Route::post('/auth/login', [LoginController::class, 'store']);

/*
|--------------------------------------------------------------------------
| API pública 
|--------------------------------------------------------------------------
*/
Route::get('/regions', [RegionController::class, 'index']);
Route::get('/provinces', [ProvinceController::class, 'index']);
Route::get('/seasons', [SeasonController::class, 'index']);

Route::get('/competitions', [CompetitionController::class, 'index']);

Route::middleware('public_league_guard')->group(function () {

    Route::get('/teams/{team}/players', [TeamPlayersController::class, 'index']);

    Route::get('/matches/{match}/report', [PublicMatchReportController::class, 'show']);
    Route::get('/matches/{match}', [PublicMatchDetailController::class, 'show']);

    Route::get('/leagues/{league}', [CompetitionController::class, 'show']);
    Route::get('/leagues/{league}/groups', [CompetitionController::class, 'groups']);
    Route::get('/leagues/{league}/siblings', [CompetitionController::class, 'siblings']);
    Route::get('/leagues/{league}/versions', [CompetitionController::class, 'versions']);

    Route::get('/leagues/{league}/detail', [PublicLeagueDetailController::class, 'show']);
    Route::get('/leagues/{league}/standings', [PublicStandingsController::class, 'show']);
    Route::get('/leagues/{league}/stats', [PublicLeagueStatsController::class, 'show']);

    // Jornadas y partidos
    Route::get('/leagues/{league}/matchdays', [PublicMatchdayController::class, 'index']);
    Route::get('/leagues/{league}/matchdays/{number}/matches', [PublicMatchController::class, 'byMatchday']);
});

/*
|--------------------------------------------------------------------------
| Auth protegida
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn (Request $r) => $r->user());
    Route::post('/auth/logout', [LogoutController::class, 'destroy']);

    // Perfil
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/password', [ProfileController::class, 'updatePassword']);

    // Ligas privadas (usuario autenticado)
    Route::post('/private/leagues', [PrivateLeaguesController::class, 'store']);
    Route::get('/my/leagues', [MyLeaguesController::class, 'index']);
    Route::get('/private/leagues/{league}/detail', [PrivateLeagueDetailController::class, 'show']);
    Route::get('/private/leagues/{league}/teams', [PrivateLeagueTeamsController::class, 'index']);

    Route::post('/private/leagues/{league}/teams', [PrivateLeagueTeamsController::class, 'store']);
    Route::put('/private/leagues/{league}/teams/{team}', [PrivateLeagueTeamsController::class, 'update']);
    Route::delete('/private/leagues/{league}/teams/{team}', [PrivateLeagueTeamsController::class, 'destroy']);
    Route::get('/private/leagues/{league}/players', [PrivateLeaguePlayersController::class, 'index']);

    Route::post('/private/leagues/{league}/players', [PrivateLeaguePlayersController::class, 'store']);
    Route::put('/private/leagues/{league}/players/{player}', [PrivateLeaguePlayersController::class, 'update']);
    Route::delete('/private/leagues/{league}/players/{player}', [PrivateLeaguePlayersController::class, 'destroy']);

    Route::post('/private/leagues/{league}/schedule/preview', [PrivateLeagueScheduleController::class, 'preview']);
    Route::post('/private/leagues/{league}/schedule/publish', [PrivateLeagueScheduleController::class, 'publish']);
    Route::get('/private/leagues/{league}/matchdays', [PrivateMatchdayController::class, 'index']);
    Route::get('/private/leagues/{league}/matchdays/{number}/matches', [PrivateMatchController::class, 'byMatchday']);
    Route::put('/private/leagues/{league}/matchdays/{number}/results', [PrivateMatchResultController::class, 'bulkUpdate']);
    Route::get('/private/leagues/{league}/standings', [PrivateLeagueStandingsController::class, 'index']);
    Route::get('/private/leagues/{league}/stats', [PrivateLeagueStatsController::class, 'index']);

    Route::post('/private/matches/{match}/report', [PrivateMatchReportController::class, 'generate']);

    /*
    |--------------------------------------------------------------------------
    | Admin 
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin')->group(function () {

        // Endpoints "globales" solo para roles globales
        Route::get('/dashboard', function () {
            return response()->json(['message' => 'Hola superadmin']);
        })->middleware('role:superadmin');

        Route::get('/stats', function () {
            return response()->json(['message' => 'Stats visibles para superadmin y admin']);
        })->middleware('role:superadmin,admin');

        // Gestión de resultados / alineaciones / eventos / locks / stats overrides / actas
        // (Estos endpoints suelen autorizar por league_memberships/canManage*, por eso NO metemos role global aquí.)
        Route::put('/leagues/{league}/matchdays/{number}/results', [AdminMatchResultController::class, 'bulkUpdate']);
        Route::put('/matches/{match}/lineups', [AdminMatchLineupController::class, 'update']);
        Route::put('/matches/{match}/events', [MatchEventsAdminController::class, 'sync']);

        Route::post('/matches/{match}/open',   [MatchLockController::class, 'open']);
        Route::post('/matches/{match}/close',  [MatchLockController::class, 'close']);
        Route::post('/matches/{match}/verify', [MatchLockController::class, 'verify']);

        Route::get('/leagues/{league}/stats/overrides', [AdminLeagueStatsController::class, 'index']);
        Route::put('/leagues/{league}/stats/overrides', [AdminLeagueStatsController::class, 'sync']);

        Route::post('/matches/{match}/report', [AdminMatchReportController::class, 'upload']);
        Route::delete('/matches/{match}/report', [AdminMatchReportController::class, 'destroy']);

        Route::middleware('role:superadmin,admin')->group(function () {
            Route::get('/competitions', [AdminCompetitionController::class, 'index']);

            Route::get('/competitions/{competition}/leagues', [AdminCompetitionController::class, 'leagues'])
                ->middleware('competition_scope');
        });
    });
});
