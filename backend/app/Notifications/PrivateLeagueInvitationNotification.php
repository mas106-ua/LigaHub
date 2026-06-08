<?php

namespace App\Notifications;

use App\Models\League;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PrivateLeagueInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly League $league,
        private readonly string $inviteUrl,
        private readonly ?User $inviter = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $inviterName = $this->inviter?->name ?: 'Un administrador';

        return (new MailMessage)
            ->subject("Invitación a la liga privada {$this->league->name}")
            ->greeting('Has recibido una invitación')
            ->line("{$inviterName} te ha invitado a unirte a la liga privada \"{$this->league->name}\".")
            ->line('Para acceder, abre el enlace e inicia sesión o crea una cuenta si todavía no tienes una.')
            ->action('Abrir invitación', $this->inviteUrl)
            ->line('Si no esperabas esta invitación, puedes ignorar este correo.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'league_id' => $this->league->id,
            'league_name' => $this->league->name,
            'invite_url' => $this->inviteUrl,
        ];
    }
}