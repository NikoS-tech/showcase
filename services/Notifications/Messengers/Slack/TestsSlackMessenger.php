<?php

namespace App\Services\Notifications\Messengers\Slack;

use App\Http\Notifications\TestsNotification;
use Illuminate\Notifications\Notification as Notifications;
use Illuminate\Support\Collection;

class TestsSlackMessenger extends SlackMessenger
{
    private bool $isPassed = false;

    protected function setWebhook(): void
    {
    }

    public function setCustomWebhook(string $webhook): void
    {
        $this->webhook = $webhook;
    }

    protected function getMessages(): Collection
    {
        return collect();
    }

    public function setMessage(array $messages): void
    {
        $this->messages = collect($messages);
    }

    public function setIsPassed(bool $isPassed): void
    {
        $this->isPassed = $isPassed;
    }

    protected function setNotification(): Notifications
    {
        return new TestsNotification($this->messages->toArray(), $this->isPassed);
    }
}