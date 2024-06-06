<?php

namespace App\Services\Notifications;

use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Notifications\Notification as Notifications;

class NotificationsService
{
    protected string $webhook;
    protected string $channel;
    protected AnonymousNotifiable $notifier;

    public function __construct(string $channel, string $webhook)
    {
        $this->channel = $channel;
        $this->webhook = $webhook;
        $this->setNotifier();
    }

    protected function setNotifier(): void
    {
        $this->notifier = Notification::route($this->channel, $this->webhook);
    }

    public function notifier(): AnonymousNotifiable
    {
        return $this->notifier;
    }

    public function notify(Notifications $notification): void
    {
        $this->notifier()->notify($notification);
    }
}