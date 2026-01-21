<?php

namespace Tests\Feature\PrivateLeagues;

use App\Models\League;
use App\Models\LeagueMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivateLeaguesFlowTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(): User
    {
        return User::factory()->create();
    }

    private function createPrivateLeagueFor(User $owner): League
    {
        $this->actingAs($owner, 'sanctum');

        $res = $this->postJson('/api/private/leagues', [
            'name' => 'Liga Test',
        ])->assertCreated();

        // OJO: en tu BE-01 seguramente devuelves { league: {...} } o { data: {...} }
        // Ajusta UNA de estas dos líneas según tu response real:
        $leagueId = $res->json('league.id') ?? $res->json('data.id');

        return League::query()->findOrFail($leagueId);
    }

    private function addMember(League $league, User $user, string $role = 'member'): void
    {
        LeagueMembership::query()->create([
            'league_id'      => $league->id,
            'user_id'        => $user->id,
            'role_in_league' => $role,
            'joined_at'      => now(),
        ]);
    }

    public function test_create_private_league_creates_owner_membership(): void
    {
        $owner = $this->createUser();

        $this->actingAs($owner, 'sanctum');

        $this->postJson('/api/private/leagues', [
            'name' => 'Mi Liga Privada',
        ])->assertCreated();

        $league = League::query()->latest('id')->first();
        $this->assertNotNull($league);
        $this->assertSame('private', $league->type);
        $this->assertSame($owner->id, (int) $league->owner_user_id);

        $this->assertDatabaseHas('league_memberships', [
            'league_id'      => $league->id,
            'user_id'        => $owner->id,
            'role_in_league' => 'owner',
        ]);
    }

    public function test_my_leagues_returns_private_leagues_with_role(): void
    {
        $owner = $this->createUser();
        $league = $this->createPrivateLeagueFor($owner);

        $this->actingAs($owner, 'sanctum');

        $res = $this->getJson('/api/my/leagues?type=private')->assertOk();

        $data = $res->json('data');
        $this->assertIsArray($data);

        $item = collect($data)->first(fn ($l) => (int) $l['id'] === $league->id);
        $this->assertNotNull($item);

        $this->assertSame('owner', $item['role_in_league']);
    }

    public function test_private_league_detail_requires_membership(): void
    {
        $owner = $this->createUser();
        $outsider = $this->createUser();
        $member = $this->createUser();

        $league = $this->createPrivateLeagueFor($owner);
        $this->addMember($league, $member, 'member');

        // Outsider -> 404
        $this->actingAs($outsider, 'sanctum');
        $this->getJson("/api/private/leagues/{$league->id}/detail")->assertStatus(404);

        // Member -> 200
        $this->actingAs($member, 'sanctum');
        $this->getJson("/api/private/leagues/{$league->id}/detail")
            ->assertOk()
            ->assertJsonPath('data.id', $league->id);

        // Owner -> 200
        $this->actingAs($owner, 'sanctum');
        $this->getJson("/api/private/leagues/{$league->id}/detail")
            ->assertOk()
            ->assertJsonPath('data.permissions.role_in_league', 'owner');
    }

    public function test_private_league_teams_crud_permissions_and_unique_name(): void
    {
        $owner = $this->createUser();
        $member = $this->createUser();
        $outsider = $this->createUser();

        $league = $this->createPrivateLeagueFor($owner);
        $this->addMember($league, $member, 'member');

        // Member puede listar
        $this->actingAs($member, 'sanctum');
        $this->getJson("/api/private/leagues/{$league->id}/teams")->assertOk();

        // Outsider no puede listar (404)
        $this->actingAs($outsider, 'sanctum');
        $this->getJson("/api/private/leagues/{$league->id}/teams")->assertStatus(404);

        // Owner crea equipo
        $this->actingAs($owner, 'sanctum');
        $create = $this->postJson("/api/private/leagues/{$league->id}/teams", [
            'name' => 'Equipo A',
        ])->assertCreated();

        $teamId = $create->json('data.id');

        // Nombre único por liga
        $this->postJson("/api/private/leagues/{$league->id}/teams", [
            'name' => 'equipo a',
        ])->assertStatus(422);

        // Owner edita
        $this->putJson("/api/private/leagues/{$league->id}/teams/{$teamId}", [
            'name' => 'Equipo A Renombrado',
            'group_name' => 'Grupo A',
        ])->assertOk();

        // Member NO puede crear/editar/borrar (403)
        $this->actingAs($member, 'sanctum');
        $this->postJson("/api/private/leagues/{$league->id}/teams", [
            'name' => 'Equipo B',
        ])->assertStatus(403);

        $this->putJson("/api/private/leagues/{$league->id}/teams/{$teamId}", [
            'name' => 'X',
        ])->assertStatus(403);

        $this->deleteJson("/api/private/leagues/{$league->id}/teams/{$teamId}")->assertStatus(403);

        // Owner borra
        $this->actingAs($owner, 'sanctum');
        $this->deleteJson("/api/private/leagues/{$league->id}/teams/{$teamId}")->assertOk();
    }

    public function test_public_endpoints_block_private_leagues(): void
    {
        $owner = $this->createUser();
        $league = $this->createPrivateLeagueFor($owner);

        // público: 404 por BE-07
        $this->getJson("/api/leagues/{$league->id}/detail")->assertStatus(404);
    }
}
