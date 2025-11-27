<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\League;
use App\Models\Region;
use App\Models\Season;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompetitionsIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_public_official_leagues_with_pagination(): void
    {
        $region = Region::factory()->create(['code' => 'AND']);
        $season = Season::factory()->create(['code' => '2025/26']);
        $category = Category::factory()->create();

        League::factory()->count(2)->create([
            'name'        => 'Preferente AND',
            'type'        => 'official',
            'visibility'  => 'public',
            'region_id'   => $region->id,
            'season_id'   => $season->id,
            'category_id' => $category->id,
        ]);

        // No debería salir
        League::factory()->create([
            'name'        => 'Privada',
            'type'        => 'private',
            'visibility'  => 'private',
            'region_id'   => $region->id,
            'season_id'   => $season->id,
            'category_id' => $category->id,
        ]);

        $res = $this->getJson('/api/competitions?region=AND&season=2025/26&per_page=10');

        $res->assertOk()
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonCount(2, 'data')
            ->assertJsonMissing(['name' => 'Privada']);
    }

    public function test_filters_by_search_term(): void
    {
        $r = Region::factory()->create(['code' => 'MAD']);
        $s = Season::factory()->create(['code' => '2025/26']);
        $c = Category::factory()->create();

        League::factory()->create([
            'name'        => 'Preferente Madrileña',
            'type'        => 'official',
            'visibility'  => 'public',
            'region_id'   => $r->id,
            'season_id'   => $s->id,
            'category_id' => $c->id,
        ]);

        League::factory()->create([
            'name'        => 'Juvenil Norte',
            'type'        => 'official',
            'visibility'  => 'public',
            'region_id'   => $r->id,
            'season_id'   => $s->id,
            'category_id' => $c->id,
        ]);

        $this->getJson('/api/competitions?region=MAD&season=2025/26&search=Preferente')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Preferente Madrileña');
    }

    public function test_returns_empty_list_when_no_results(): void
    {
        $this->getJson('/api/competitions?region=ZZZ&season=2099/00')
            ->assertOk()
            ->assertJsonPath('data', [])
            ->assertJsonPath('meta.total', 0);
    }

    public function test_validates_bad_params(): void
    {
        $this->getJson('/api/competitions?per_page=-1')
            ->assertStatus(422);
    }
}
