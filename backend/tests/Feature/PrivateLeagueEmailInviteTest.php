<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\League;
use App\Models\LeagueMembership;
use App\Models\Region;
use App\Models\Season;
use App\Models\User;
use App\Notifications\PrivateLeagueInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class PrivateLeagueEmailInviteTest extends TestCase
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

    public function test_manager_can_send_private_league_invitation_by_email(): void
    {
        Notification::fake();

        $owner = User::factory()->create(['role' => 'user']);
        $league = $this->seedPrivateLeague($owner);

        $response = $this->actingAs($owner, 'sanctum')
            ->postJson("/api/private/leagues/{$league->id}/invite-email", [
                'email' => 'invitado@example.com',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Invitación enviada correctamente.')
            ->assertJsonPath('data.email', 'invitado@example.com')
            ->assertJsonPath('data.league.id', $league->id)
            ->assertJsonPath('data.league.name', $league->name)
            ->assertJsonPath('data.league.visibility', 'by_link')
            ->assertJsonStructure([
                'data' => [
                    'email',
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

        Notification::assertSentOnDemand(
            PrivateLeagueInvitationNotification::class,
            function ($notification, array $channels, object $notifiable) {
                return in_array('mail', $channels, true)
                    && ($notifiable->routes['mail'] ?? null) === 'invitado@example.com';
            }
        );
    }

    public function test_email_invitation_reuses_existing_invite_token(): void
    {
        Notification::fake();

        $owner = User::factory()->create(['role' => 'user']);
        $token = (string) Str::uuid();

        $league = $this->seedPrivateLeague($owner, [
            'visibility' => 'by_link',
            'access_uuid' => $token,
        ]);

        $response = $this->actingAs($owner, 'sanctum')
            ->postJson("/api/private/leagues/{$league->id}/invite-email", [
                'email' => 'invitado@example.com',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.frontend_path', "/mis-ligas/invitacion/{$token}");

        $league->refresh();

        $this->assertSame($token, $league->access_uuid);
        $this->assertSame('by_link', $league->visibility);

        Notification::assertSentOnDemand(PrivateLeagueInvitationNotification::class);
    }

    public function test_email_is_required_and_must_be_valid(): void
    {
        Notification::fake();

        $owner = User::factory()->create(['role' => 'user']);
        $league = $this->seedPrivateLeague($owner);

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/private/leagues/{$league->id}/invite-email", [
                'email' => '',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/private/leagues/{$league->id}/invite-email", [
                'email' => 'correo-no-valido',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        Notification::assertNothingSent();
    }

    public function test_non_manager_cannot_send_invitation_email(): void
    {
        Notification::fake();

        $owner = User::factory()->create(['role' => 'user']);
        $otherUser = User::factory()->create(['role' => 'user']);

        $league = $this->seedPrivateLeague($owner);

        $this->actingAs($otherUser, 'sanctum')
            ->postJson("/api/private/leagues/{$league->id}/invite-email", [
                'email' => 'invitado@example.com',
            ])
            ->assertNotFound();

        $league->refresh();

        $this->assertSame('private', $league->visibility);
        $this->assertNull($league->access_uuid);

        Notification::assertNothingSent();
    }

    public function test_invitation_email_cannot_be_sent_for_inactive_league(): void
    {
        Notification::fake();

        $owner = User::factory()->create(['role' => 'user']);

        $league = $this->seedPrivateLeague($owner, [
            'is_active' => false,
        ]);

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/private/leagues/{$league->id}/invite-email", [
                'email' => 'invitado@example.com',
            ])
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                'No se puede enviar una invitación para una liga inactiva.'
            );

        $league->refresh();

        $this->assertNull($league->access_uuid);

        Notification::assertNothingSent();
    }

    public function test_invitation_email_cannot_be_sent_for_official_league(): void
    {
        Notification::fake();

        $owner = User::factory()->create(['role' => 'user']);

        $league = $this->seedPrivateLeague($owner, [
            'type' => 'official',
            'visibility' => 'public',
            'access_uuid' => null,
        ]);

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/private/leagues/{$league->id}/invite-email", [
                'email' => 'invitado@example.com',
            ])
            ->assertNotFound();

        Notification::assertNothingSent();
    }
}