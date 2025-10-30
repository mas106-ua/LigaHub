<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_puede_hacer_login_con_credenciales_validas(): void
    {
        $user = User::factory()->create([
            'email' => 'mario@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'mario@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Inicio de sesión correcto.',
            ])
            ->assertJsonPath('user.email', 'mario@example.com');

        $this->assertAuthenticatedAs($user);
    }

    public function test_no_puede_hacer_login_con_credenciales_invalidas(): void
    {
        $user = User::factory()->create([
            'email' => 'mario@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'mario@example.com',
            'password' => 'wrong',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertGuest();
    }

    public function test_usuario_autenticado_puede_ver_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->getJson('/api/user');

        $response->assertOk()
            ->assertJsonPath('email', $user->email);
    }

    public function test_invitado_no_puede_ver_user(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    }

    public function test_usuario_puede_hacer_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->postJson('/api/auth/logout');

        $response->assertOk()
            ->assertJson([
                'message' => 'Sesión cerrada.',
            ]);

        $this->assertEquals('Sesión cerrada.', $response->json('message'));
    }
}
