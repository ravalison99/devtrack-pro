<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Socle des notifications de l'application.
 *
 * Chaque notification fournit son message, son lien (chemin relatif dans l'application) et son icône :
 * la page « Mes notifications » n'a donc plus besoin de connaître chaque type.
 *
 * - La notification dans l'application est toujours créée, immédiatement.
 * - Le mail n'est envoyé que pour les événements importants (parMail()), et passe par la file d'attente
 *   afin qu'un serveur mail lent ou en panne ne bloque jamais l'action de l'utilisateur.
 */
abstract class BaseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    /** Texte affiché dans l'application et repris dans le mail. */
    abstract protected function message(): string;

    /** Chemin relatif vers la page concernée (ex. « /tasks/5 »). */
    abstract protected function chemin(): string;

    protected function sujet(): string
    {
        return 'DevTrack Pro — nouvelle notification';
    }

    protected function libelleLien(): string
    {
        return 'Ouvrir';
    }

    protected function icone(): string
    {
        return 'bi-bell';
    }

    /** Données complémentaires conservées avec la notification (identifiants, statuts...). */
    protected function donnees(): array
    {
        return [];
    }

    /** Vrai pour les événements importants, qui méritent aussi un mail. */
    protected function parMail(): bool
    {
        return false;
    }

    public function via(object $notifiable): array
    {
        return $this->parMail() ? ['database', 'mail'] : ['database'];
    }

    /**
     * La notification dans l'application est écrite tout de suite (sans dépendre d'un « queue worker ») ;
     * seul le mail passe par la file d'attente configurée.
     *
     * @return array<string, string>
     */
    public function viaConnections(): array
    {
        return [
            'database' => 'sync',
            'mail' => config('queue.default'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->sujet())
            ->line($this->message())
            ->action($this->libelleLien(), url($this->chemin()));
    }

    public function toArray(object $notifiable): array
    {
        return array_merge([
            'message' => $this->message(),
            'url' => $this->chemin(),
            'icone' => $this->icone(),
        ], $this->donnees());
    }
}
