<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Competition;
use App\Models\League;
use App\Models\Region;
use App\Models\Season;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCompetitionsTest extends TestCase
{
    use RefreshDatabase;

    private function seedMinimum(): array
    {
        $cat = Category::query()->create([
            'name'   => 'Senior',
            'level'  => 'amateur',
            'gender' => 'male',
        ]);

        $reg = Region::query()->create([
            'name' => 'Andalucía',
            'code' => 'AND',
        ]);

        $season = Season::query()->create([
            'code'       => '2024/25',
            'start_date' => '2024-08-01',
            'end_date'   => '2025-06-30',
        ]);

        $competition = Competition::query()->create([
            'name'        => 'Tercera Federación',
            'code'        => 'tercera-federacion',
            'category_id' => $cat->id,
            'level'       => 'amateur',
            'gender'      => 'male',
            'region_id'   => $reg->id,
            'province_id' => null,
            'type'        => 'official',
            'is_active'   => true,
        ]);

        League::query()->create([
            'name'           => 'Tercera Federación – Andalucía',
            'type'           => 'official',
            'visibility'     => 'public',
            'access_uuid'    => null,
            'owner_user_id'  => null,
            'is_active'      => true,
            'season_id'      => $season->id,
            'region_id'      => $reg->id,
            'province_id'    => null,
            'category_id'    => $cat->id,
            'competition_id' => $competition->id,
            'group_name'     => null,
        ]);

        return [$competition];
    }

    public function test_requires_admin_role(): void
    {
        $this->seedMinimum();

        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->getJson('/api/admin/competitions')
            ->assertStatus(403);
    }

    public function test_admin_can_list_competitions_and_leagues(): void
    {
        [$competition] = $this->seedMinimum();

        $admin = User::factory()->create(['role' => 'admin']);

        \DB::table('leagues')->insert([
            'competition_id' => $competition->id,
            'owner_user_id'  => $admin->id,
            'type'           => 'official',
            'name'           => 'Liga acceso admin',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/competitions?level=amateur&search=tercera')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/competitions/'.$competition->id.'/leagues')
            ->assertOk()
            ->assertJsonStructure([
                'competition' => ['id','name','code'],
                'data',
            ]);
    }

}
