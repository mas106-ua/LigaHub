<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\League;
use App\Models\LeagueMembership;
use App\Models\Region;
use App\Models\Season;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyPrivateLeaguesListingTest extends TestCase
{
    use RefreshDatabase;

    private function makeSeason(string $code): Season
    {
        return Season::query()->firstOrCreate(
            ['code' => $code],
            [
                'start_date' => str_starts_with($code, '2025')
                    ? '2025-08-01'
                    : '2024-08-01',
                'end_date' => str_starts_with($code, '2025')
                    ? '2026-06-30'
                    : '2025-06-30',
            ]
        );
    }

    private function makePrivateLeagueForUser(
        User $user,
        string $name,
        string $role = 'member',
        string $seasonCode = '2025/26',
        array $overrides = []
    ): League {
        $season = $this->makeSeason($seasonCode);
        $category = Category::factory()->create([
            'name' => 'Senior',
            'level' => 'amateur',
            'gender' => 'male',
        ]);
        $region = Region::factory()->create();

        $league = League::factory()->create(array_merge([
            'name' => $name,
            'type' => 'private',
            'visibility' => 'private',
            'season_id' => $season->id,
            'category_id' => $category->id,
            'region_id' => $region->id,
            'owner_user_id' => $role === 'owner' ? $user->id : null,
            'is_active' => true,
        ], $overrides));

        LeagueMembership::query()->create([
            'league_id' => $league->id,
            'user_id' => $user->id,
            'role_in_league' => $role,
            'joined_at' => now(),
        ]);

        return $league;
    }

    public function test_user_can_list_all_own_private_leagues_with_role_and_metadata(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $otherUser = User::factory()->create(['role' => 'user']);

        $ownerLeague = $this->makePrivateLeagueForUser(
            user: $user,
            name: 'Cartageneros',
            role: 'owner',
            seasonCode: '2025/26'
        );

        $memberLeague = $this->makePrivateLeagueForUser(
            user: $user,
            name: 'Liga amigos',
            role: 'member',
            seasonCode: '2024/25'
        );

        $this->makePrivateLeagueForUser(
            user: $otherUser,
            name: 'Liga de otro usuario',
            role: 'owner',
            seasonCode: '2025/26'
        );

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/my/leagues')
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.filters.type', 'private')
            ->assertJsonFragment([
                'id' => $ownerLeague->id,
                'name' => 'Cartageneros',
                'role_in_league' => 'owner',
                'role_label' => 'Propietario',
                'can_manage' => true,
            ])
            ->assertJsonFragment([
                'id' => $memberLeague->id,
                'name' => 'Liga amigos',
                'role_in_league' => 'member',
                'role_label' => 'Miembro',
                'can_manage' => false,
            ])
            ->assertJsonMissing([
                'name' => 'Liga de otro usuario',
            ]);
    }

    public function test_user_can_filter_private_leagues_by_search(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->makePrivateLeagueForUser($user, 'Cartageneros', 'owner');
        $this->makePrivateLeagueForUser($user, 'Liga amigos', 'member');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/my/leagues?search=cart')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonFragment([
                'name' => 'Cartageneros',
            ])
            ->assertJsonMissing([
                'name' => 'Liga amigos',
            ]);
    }

    public function test_user_can_filter_private_leagues_by_role(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->makePrivateLeagueForUser($user, 'Cartageneros', 'owner');
        $this->makePrivateLeagueForUser($user, 'Liga amigos', 'member');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/my/leagues?role=owner')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonFragment([
                'name' => 'Cartageneros',
                'role_in_league' => 'owner',
            ])
            ->assertJsonMissing([
                'name' => 'Liga amigos',
            ]);
    }

    public function test_user_can_filter_private_leagues_by_season_code(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->makePrivateLeagueForUser($user, 'Temporada actual', 'owner', '2025/26');
        $this->makePrivateLeagueForUser($user, 'Temporada anterior', 'member', '2024/25');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/my/leagues?season=2025%2F26')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonFragment([
                'name' => 'Temporada actual',
            ])
            ->assertJsonMissing([
                'name' => 'Temporada anterior',
            ]);
    }

    public function test_user_can_request_official_memberships_if_needed(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $privateLeague = $this->makePrivateLeagueForUser(
            user: $user,
            name: 'Privada',
            role: 'member'
        );

        $officialLeague = $this->makePrivateLeagueForUser(
            user: $user,
            name: 'Oficial vinculada',
            role: 'admin',
            seasonCode: '2025/26',
            overrides: [
                'type' => 'official',
                'visibility' => 'public',
            ]
        );

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/my/leagues?type=official')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonFragment([
                'id' => $officialLeague->id,
                'name' => 'Oficial vinculada',
                'type' => 'official',
            ])
            ->assertJsonMissing([
                'id' => $privateLeague->id,
                'name' => 'Privada',
            ]);
    }

    public function test_invalid_filters_return_validation_error(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/my/leagues?role=invalid-role')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['role']);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/my/leagues?type=invalid-type')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }
}