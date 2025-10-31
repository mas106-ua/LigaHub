<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // ejecutamos seeds para tener el superadmin
        $this->seed(\Database\Seeders\SuperAdminSeeder::class);
    }

    public function test_superadmin_puede_acceder_a_ruta_admin()
    {
        $superadmin = User::where('email', 'superadmin@tfg.local')->first();

        $response = $this->actingAs($superadmin)
            ->getJson('/api/admin/dashboard');

        $response->assertOk()
            ->assertJsonFragment([
                'message' => 'Hola superadmin',
            ]);
    }

    public function test_user_normal_no_puede_acceder_a_ruta_admin()
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/admin/dashboard');

        $response->assertStatus(403);
    }

    public function test_invitado_no_puede_acceder_a_ruta_admin()
    {
        $response = $this->getJson('/api/admin/dashboard');

        $response->assertStatus(401);
    }
}
