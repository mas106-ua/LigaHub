<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\League;
use App\Services\PrivateLeagueStatsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrivateLeagueStatsController extends Controller
{
    public function __construct(
        private readonly PrivateLeagueStatsService $service
    ) {}

    public function index(Request $request, League $league)
    {
        if ($league->type !== 'private') {
            abort(404);
        }

        $user = $request->user();

        $isOwner = (int) $league->owner_user_id === (int) $user->id;

        $isMember = DB::table('league_memberships')
            ->where('league_id', $league->id)
            ->where('user_id', $user->id)
            ->exists();

        if (! $isOwner && ! $isMember) {
            abort(404);
        }

        return response()->json([
            'data' => $this->service->calculate($league, $request),
        ]);
    }
}
