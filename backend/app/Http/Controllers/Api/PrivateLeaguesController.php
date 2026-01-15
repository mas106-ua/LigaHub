<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PrivateLeagueStoreRequest;
use App\Models\League;
use App\Models\LeagueMembership;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PrivateLeaguesController extends Controller
{
    /**
     * POST /api/private/leagues
     *
     * Crea una liga privada (formulario mínimo) y asigna al creador como owner/admin-liga.
     */
    public function store(PrivateLeagueStoreRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $visibility = $data['visibility'] ?? 'private';
        $accessUuid = $visibility === 'by_link' ? (string) Str::uuid() : null;

        $league = DB::transaction(function () use ($user, $data, $visibility, $accessUuid) {
            $league = League::create([
                'name'          => $data['name'],
                'type'          => 'private',
                'visibility'    => $visibility,
                'access_uuid'   => $accessUuid,
                'owner_user_id' => $user->id,

                // Opcionales
                'season_id'   => $data['season_id'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'region_id'   => $data['region_id'] ?? null,
                'province_id' => $data['province_id'] ?? null,

                // En privadas no aplican por defecto
                'competition_id' => null,
                'group_name'     => null,
            ]);

            LeagueMembership::create([
                'league_id'      => $league->id,
                'user_id'        => $user->id,
                'role_in_league' => 'owner',
                'joined_at'      => now(),
            ]);

            return $league;
        });

        return response()->json([
            'message' => 'Liga privada creada.',
            'league'  => [
                'id'            => $league->id,
                'name'          => $league->name,
                'type'          => $league->type,
                'visibility'    => $league->visibility,
                'access_uuid'   => $league->access_uuid,
                'season_id'     => $league->season_id,
                'owner_user_id' => $league->owner_user_id,
                'role_in_league'=> 'owner',
            ],
        ], 201);
    }
}
