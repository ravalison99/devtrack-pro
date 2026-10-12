<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

abstract class BaseNotification extends Notification
{
    use Queueable;

    /**
     * @return array{0: string, 1: string, 2: string, 3: string} [sujet, message, libellé du lien, url]
     */
    abstract protected function contenuMail(): array;

    abstract public function toArray(object $notifiable): array;

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        [$sujet, $message, $libelle, $url] = $this->contenuMail();

        return (new MailMessage)->subject($sujet)->line($message)->action($libelle, $url);
    }
}
