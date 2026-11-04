<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ActivateAccount extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $token) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('activation.show', ['token' => $this->token, 'email' => $notifiable->email]);

        return (new MailMessage)
            ->subject('Ative a sua conta GymFlow')
            ->greeting('Olá, '.$notifiable->name.'!')
            ->line('Foi criada uma conta para si no GymFlow. Defina a sua palavra-passe para a ativar.')
            ->action('Ativar conta', $url)
            ->line('Esta ligação expira em 48 horas.');
    }
}
