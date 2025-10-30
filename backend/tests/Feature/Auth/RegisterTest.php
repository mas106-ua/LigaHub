<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_valido_se_registra()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Mario',
            'email' => 'mario@example.com',
            'password' => 'Secret123',
            'password_confirmation' => 'Secret123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('user.email', 'mario@example.com');

        $this->assertDatabaseHas('users', ['email' => 'mario@example.com']);
    }

    public function test_email_duplicado()
    {
        User::factory()->create(['email' => 'dup@example.com']);

        $response = $this->postJson('/api/auth/register', [
            'email' => 'dup@example.com',
            'password' => 'Secret123',
            'password_confirmation' => 'Secret123',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_password_invalida()
    {
        $response = $this->postJson('/api/auth/register', [
            'email' => 'bad@example.com',
            'password' => 'short',
            'password_confirmation' => 'other',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }
}
