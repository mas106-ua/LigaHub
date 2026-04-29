<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\League;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PrivateLeagueInviteController extends Controller
{
    /**
     * POST /api/private/leagues/{league}/invite-link
     *
     * Genera o reutiliza el enlace único de invitación de una liga privada.
     * No envía correos ni vincula usuarios; eso se hará en tareas posteriores.
     */
    public function store(Request $request, League $league): JsonResponse
    {
        if ($league->type !== 'private') {
            abort(404);
        }

        $user = $request->user();

        if (! $user || ! $this->canManagePrivateLeague($user, $league)) {
            abort(404);
        }

        if (! $league->is_active) {
            return response()->json([
                'message' => 'No se puede generar una invitación para una liga inactiva.',
            ], 409);
        }

        if (! $league->access_uuid || ! Str::isUuid((string) $league->access_uuid)) {
            $league->access_uuid = $this->generateUniqueToken();
        }

        if ($league->visibility !== 'by_link') {
            $league->visibility = 'by_link';
        }

        $league->save();

        return response()->json([
            'message' => 'Enlace de invitación disponible.',
            'data' => [
                'token' => $league->access_uuid,
                'invite_url' => $this->buildInviteUrl($request, $league->access_uuid),
                'frontend_path' => $this->buildFrontendPath($league->access_uuid),
                'league' => [
                    'id' => $league->id,
                    'name' => $league->name,
                    'visibility' => $league->visibility,
                ],
            ],
        ]);
    }

    /**
     * GET /api/private-league-invitations/{token}
     *
     * Resuelve públicamente un token de invitación.
     * Devuelve datos mínimos para mostrar la pantalla de invitación.
     */
    public function show(string $token): JsonResponse
    {
        if (! Str::isUuid($token)) {
            abort(404);
        }

        $league = League::query()
            ->where('type', 'private')
            ->where('visibility', 'by_link')
            ->where('is_active', true)
            ->where('access_uuid', $token)
            ->with([
                'season:id,code',
                'category:id,name',
                'region:id,code,name',
            ])
            ->first();

        if (! $league) {
            abort(404);
        }

        return response()->json([
            'data' => [
                'token' => $league->access_uuid,
                'requires_auth' => true,
                'frontend_path' => $this->buildFrontendPath($league->access_uuid),
                'league' => [
                    'id' => $league->id,
                    'name' => $league->name,
                    'season' => $league->season ? [
                        'id' => $league->season->id,
                        'code' => $league->season->code,
                    ] : null,
                    'category' => $league->category ? [
                        'id' => $league->category->id,
                        'name' => $league->category->name,
                    ] : null,
                    'region' => $league->region ? [
                        'id' => $league->region->id,
                        'code' => $league->region->code,
                        'name' => $league->region->name,
                    ] : null,
                ],
            ],
        ]);
    }

    private function canManagePrivateLeague(User $user, League $league): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($league->owner_user_id && (int) $league->owner_user_id === (int) $user->id) {
            return true;
        }

        return DB::table('league_memberships')
            ->where('league_id', $league->id)
            ->where('user_id', $user->id)
            ->whereIn('role_in_league', ['owner', 'admin'])
            ->exists();
    }

    private function generateUniqueToken(): string
    {
        do {
            $token = (string) Str::uuid();
        } while (League::query()->where('access_uuid', $token)->exists());

        return $token;
    }

    private function buildFrontendPath(string $token): string
    {
        return "/mis-ligas/invitacion/{$token}";
    }

    private function buildInviteUrl(Request $request, string $token): string
    {
        $frontendUrl = rtrim(
            (string) config('app.frontend_url', env('FRONTEND_URL', '')),
            '/'
        );

        if ($frontendUrl === '') {
            $frontendUrl = rtrim((string) $request->headers->get('origin', ''), '/');
        }

        if ($frontendUrl === '') {
            $frontendUrl = $request->getSchemeAndHttpHost();
        }

        return $frontendUrl . $this->buildFrontendPath($token);
    }
}