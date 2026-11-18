<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PaymentNotification extends Notification
{
    use Queueable;

    public function __construct(private array $payload) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->payload;
    }
}
