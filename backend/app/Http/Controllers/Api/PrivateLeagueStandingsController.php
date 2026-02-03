<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\League;
use App\Services\PrivateLeagueStandingsService;
use Illuminate\Http\Request;

class PrivateLeagueStandingsController extends Controller
{
    public function __construct(
        private readonly PrivateLeagueStandingsService $service
    ) {}

    public function index(Request $request, League $league)
    {
        // Solo ligas privadas
        if ($league->type !== 'private') {
            abort(404);
        }

        $user = $request->user();

        // Solo miembros u owner
        $isOwner = $league->owner_user_id === $user->id;
        $isMember = $league->members()
            ->where('user_id', $user->id)
            ->exists();

        if (! $isOwner && ! $isMember) {
            abort(404);
        }

        return response()->json([
            'data' => $this->service->calculate($league),
        ]);
    }
}
