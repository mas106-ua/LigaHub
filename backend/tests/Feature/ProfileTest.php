<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function usuario_autenticado_puede_ver_su_perfil()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $res = $this->getJson('/api/profile');
        $res->assertOk()
            ->assertJsonPath('email', $user->email);
    }

    /** @test */
    public function invitado_no_puede_ver_perfil()
    {
        $this->getJson('/api/profile')->assertStatus(401);
    }

    /** @test */
    public function puede_actualizar_nombre_y_avatar_validos()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $this->actingAs($user);

        $file = UploadedFile::fake()->create('avatar.png', 150, 'image/png');

        $res = $this->putJson('/api/profile', [
            'name'   => 'Nuevo Nombre',
            'avatar' => $file,
        ]);

        $res->assertOk()
            ->assertJsonPath('user.name', 'Nuevo Nombre')
            ->assertJsonStructure(['user' => ['avatar_url']]);

        $user->refresh();
        $path = str($user->avatar_url)->after('/storage/')->value();

        $this->assertEquals('Nuevo Nombre', $user->name);
        $this->assertTrue(Storage::disk('public')->exists($path));
    }

    /** @test */
    public function actualizar_con_nombre_invalido_devuelve_422()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->putJson('/api/profile', ['name' => ''])
            ->assertStatus(422);
    }

    /** @test */
    public function puede_cambiar_contrasena_si_actual_es_correcta()
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpass123'),
        ]);
        $this->actingAs($user);

        $this->putJson('/api/profile/password', [
            'current_password'      => 'oldpass123',
            'password'              => 'newpass123',
            'password_confirmation' => 'newpass123',
        ])->assertOk();

        $this->assertTrue(Hash::check('newpass123', $user->fresh()->password));
    }

    /** @test */
    public function cambiar_contrasena_falla_si_actual_no_coincide()
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpass123'),
        ]);
        $this->actingAs($user);

        $this->putJson('/api/profile/password', [
            'current_password'      => 'mala',
            'password'              => 'newpass123',
            'password_confirmation' => 'newpass123',
        ])->assertStatus(422);
    }
}
