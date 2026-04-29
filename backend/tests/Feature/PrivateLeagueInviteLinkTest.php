<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\League;
use App\Models\LeagueMembership;
use App\Models\Region;
use App\Models\Season;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PrivateLeagueInviteLinkTest extends TestCase
{
    use RefreshDatabase;

    private function seedPrivateLeague(User $owner, array $overrides = []): League
    {
        $category = Category::query()->create([
            'name' => 'Senior',
            'level' => 'amateur',
            'gender' => 'male',
        ]);

        $region = Region::query()->create([
            'name' => 'Murcia',
            'code' => 'MUR',
        ]);

        $season = Season::query()->create([
            'code' => '2025/26',
            'start_date' => '2025-08-01',
            'end_date' => '2026-06-30',
        ]);

        $league = League::query()->create(array_merge([
            'name' => 'Liga privada de prueba',
            'type' => 'private',
            'visibility' => 'private',
            'access_uuid' => null,
            'owner_user_id' => $owner->id,
            'is_active' => true,
            'season_id' => $season->id,
            'region_id' => $region->id,
            'category_id' => $category->id,
        ], $overrides));

        LeagueMembership::query()->create([
            'league_id' => $league->id,
            'user_id' => $owner->id,
            'role_in_league' => 'owner',
            'joined_at' => now(),
        ]);

        return $league;
    }

    public function test_manager_can_generate_invite_link_for_private_league(): void
    {
        $owner = User::factory()->create(['role' => 'user']);
        $league = $this->seedPrivateLeague($owner);

        $response = $this->actingAs($owner, 'sanctum')
            ->postJson("/api/private/leagues/{$league->id}/invite-link");

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Enlace de invitación disponible.')
            ->assertJsonPath('data.league.id', $league->id)
            ->assertJsonPath('data.league.name', $league->name)
            ->assertJsonPath('data.league.visibility', 'by_link')
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'invite_url',
                    'frontend_path',
                    'league' => [
                        'id',
                        'name',
                        'visibility',
                    ],
                ],
            ]);

        $league->refresh();

        $this->assertSame('by_link', $league->visibility);
        $this->assertNotNull($league->access_uuid);
        $this->assertTrue(Str::isUuid($league->access_uuid));
    }

    public function test_existing_invite_link_is_idempotent(): void
    {
        $owner = User::factory()->create(['role' => 'user']);
        $token = (string) Str::uuid();

        $league = $this->seedPrivateLeague($owner, [
            'visibility' => 'by_link',
            'access_uuid' => $token,
        ]);

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/private/leagues/{$league->id}/invite-link")
            ->assertOk()
            ->assertJsonPath('data.token', $token);

        $league->refresh();

        $this->assertSame($token, $league->access_uuid);
        $this->assertSame('by_link', $league->visibility);
    }

    public function test_public_valid_token_resolves_private_league_with_minimal_data(): void
    {
        $owner = User::factory()->create(['role' => 'user']);
        $token = (string) Str::uuid();

        $league = $this->seedPrivateLeague($owner, [
            'visibility' => 'by_link',
            'access_uuid' => $token,
        ]);

        $this->getJson("/api/private-league-invitations/{$token}")
            ->assertOk()
            ->assertJsonPath('data.token', $token)
            ->assertJsonPath('data.requires_auth', true)
            ->assertJsonPath('data.frontend_path', "/mis-ligas/invitacion/{$token}")
            ->assertJsonPath('data.league.id', $league->id)
            ->assertJsonPath('data.league.name', $league->name)
            ->assertJsonMissing([
                'owner_user_id' => $owner->id,
            ]);
    }

    public function test_invalid_or_unknown_token_returns_404(): void
    {
        $this->getJson('/api/private-league-invitations/not-a-valid-token')
            ->assertNotFound();

        $this->getJson('/api/private-league-invitations/' . Str::uuid())
            ->assertNotFound();
    }

    public function test_non_manager_cannot_generate_invite_link(): void
    {
        $owner = User::factory()->create(['role' => 'user']);
        $otherUser = User::factory()->create(['role' => 'user']);

        $league = $this->seedPrivateLeague($owner);

        $this->actingAs($otherUser, 'sanctum')
            ->postJson("/api/private/leagues/{$league->id}/invite-link")
            ->assertNotFound();

        $league->refresh();

        $this->assertSame('private', $league->visibility);
        $this->assertNull($league->access_uuid);
    }

    public function test_invite_link_cannot_be_generated_for_official_league(): void
    {
        $owner = User::factory()->create(['role' => 'user']);

        $league = $this->seedPrivateLeague($owner, [
            'type' => 'official',
            'visibility' => 'public',
            'access_uuid' => null,
        ]);

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/private/leagues/{$league->id}/invite-link")
            ->assertNotFound();
    }

    public function test_inactive_private_league_cannot_generate_invite_link(): void
    {
        $owner = User::factory()->create(['role' => 'user']);

        $league = $this->seedPrivateLeague($owner, [
            'is_active' => false,
        ]);

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/private/leagues/{$league->id}/invite-link")
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                'No se puede generar una invitación para una liga inactiva.'
            );

        $league->refresh();

        $this->assertNull($league->access_uuid);
    }
}