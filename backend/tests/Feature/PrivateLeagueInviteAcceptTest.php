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

class PrivateLeagueInviteAcceptTest extends TestCase
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
            'visibility' => 'by_link',
            'access_uuid' => (string) Str::uuid(),
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

    public function test_authenticated_user_can_accept_private_league_invitation(): void
    {
        $owner = User::factory()->create(['role' => 'user']);
        $invitedUser = User::factory()->create(['role' => 'user']);

        $league = $this->seedPrivateLeague($owner);
        $token = $league->access_uuid;

        $response = $this->actingAs($invitedUser, 'sanctum')
            ->postJson("/api/private-league-invitations/{$token}/accept");

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Invitación aceptada correctamente.')
            ->assertJsonPath('data.accepted', true)
            ->assertJsonPath('data.already_member', false)
            ->assertJsonPath('data.league.id', $league->id)
            ->assertJsonPath('data.league.name', $league->name)
            ->assertJsonPath('data.membership.role_in_league', 'member')
            ->assertJsonPath('data.redirect_to', "/mis-ligas/{$league->id}");

        $this->assertDatabaseHas('league_memberships', [
            'league_id' => $league->id,
            'user_id' => $invitedUser->id,
            'role_in_league' => 'member',
        ]);
    }

    public function test_accepting_invitation_is_idempotent_and_does_not_duplicate_membership(): void
    {
        $owner = User::factory()->create(['role' => 'user']);
        $invitedUser = User::factory()->create(['role' => 'user']);

        $league = $this->seedPrivateLeague($owner);
        $token = $league->access_uuid;

        LeagueMembership::query()->create([
            'league_id' => $league->id,
            'user_id' => $invitedUser->id,
            'role_in_league' => 'member',
            'joined_at' => now(),
        ]);

        $this->actingAs($invitedUser, 'sanctum')
            ->postJson("/api/private-league-invitations/{$token}/accept")
            ->assertOk()
            ->assertJsonPath('message', 'Ya perteneces a esta liga privada.')
            ->assertJsonPath('data.accepted', true)
            ->assertJsonPath('data.already_member', true)
            ->assertJsonPath('data.membership.role_in_league', 'member');

        $count = LeagueMembership::query()
            ->where('league_id', $league->id)
            ->where('user_id', $invitedUser->id)
            ->count();

        $this->assertSame(1, $count);
    }

    public function test_accepting_invitation_does_not_reduce_existing_admin_permissions(): void
    {
        $owner = User::factory()->create(['role' => 'user']);
        $adminUser = User::factory()->create(['role' => 'user']);

        $league = $this->seedPrivateLeague($owner);
        $token = $league->access_uuid;

        LeagueMembership::query()->create([
            'league_id' => $league->id,
            'user_id' => $adminUser->id,
            'role_in_league' => 'admin',
            'joined_at' => now(),
        ]);

        $this->actingAs($adminUser, 'sanctum')
            ->postJson("/api/private-league-invitations/{$token}/accept")
            ->assertOk()
            ->assertJsonPath('data.already_member', true)
            ->assertJsonPath('data.membership.role_in_league', 'admin');

        $this->assertDatabaseHas('league_memberships', [
            'league_id' => $league->id,
            'user_id' => $adminUser->id,
            'role_in_league' => 'admin',
        ]);

        $this->assertDatabaseMissing('league_memberships', [
            'league_id' => $league->id,
            'user_id' => $adminUser->id,
            'role_in_league' => 'member',
        ]);
    }

    public function test_unauthenticated_user_cannot_accept_invitation(): void
    {
        $owner = User::factory()->create(['role' => 'user']);
        $league = $this->seedPrivateLeague($owner);

        $this->postJson("/api/private-league-invitations/{$league->access_uuid}/accept")
            ->assertUnauthorized();
    }

    public function test_invalid_or_unknown_token_returns_404(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/private-league-invitations/not-a-valid-token/accept')
            ->assertNotFound();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/private-league-invitations/' . Str::uuid() . '/accept')
            ->assertNotFound();
    }

    public function test_cannot_accept_invitation_for_inactive_private_league(): void
    {
        $owner = User::factory()->create(['role' => 'user']);
        $invitedUser = User::factory()->create(['role' => 'user']);

        $league = $this->seedPrivateLeague($owner, [
            'is_active' => false,
        ]);

        $this->actingAs($invitedUser, 'sanctum')
            ->postJson("/api/private-league-invitations/{$league->access_uuid}/accept")
            ->assertNotFound();

        $this->assertDatabaseMissing('league_memberships', [
            'league_id' => $league->id,
            'user_id' => $invitedUser->id,
        ]);
    }

    public function test_cannot_accept_invitation_if_league_is_not_visible_by_link(): void
    {
        $owner = User::factory()->create(['role' => 'user']);
        $invitedUser = User::factory()->create(['role' => 'user']);

        $league = $this->seedPrivateLeague($owner, [
            'visibility' => 'private',
        ]);

        $this->actingAs($invitedUser, 'sanctum')
            ->postJson("/api/private-league-invitations/{$league->access_uuid}/accept")
            ->assertNotFound();

        $this->assertDatabaseMissing('league_memberships', [
            'league_id' => $league->id,
            'user_id' => $invitedUser->id,
        ]);
    }
}