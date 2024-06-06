<?php

namespace App\Services\Notifications\Messengers;

use App\Http\Models\Logs;
use App\Services\Notifications\NotificationsService;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Notifications\Notification as Notifications;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

abstract class Messenger
{
    protected string $channel;
    protected ?string $webhook;

    protected Collection $messages;
    protected string $title = 'Notification';

    abstract protected function getMessages(): Collection;

    abstract protected function setWebhook(): void;

    abstract protected function setNotification(): Notifications;

    abstract protected function prepareMessage(array $data, string $desc): string;

    public function __construct($message = null)
    {
        $this->setWebhook();
        $this->messages = $message ? collect()->add($message) : $this->getMessages();
    }

    protected function prepareMessages(): Collection
    {
        return $this->messages->map(function (Arrayable $message) {
            return $this->prepareMessage($message->toArray(), $this->title);
        });
    }

    protected static function getDateTime($diff = '-5 minutes'): string
    {
        $date = new Carbon();
        $date->modify($diff);
        return $date->format('Y-m-d H:i:s');
    }

    protected function send(Notifications $notification): void
    {
        if ($this->channel && $this->webhook) {
            $service = new NotificationsService($this->channel, $this->webhook);
            $service->notify($notification);
        }
    }

    public function init(): void
    {
        try {
            if ($this->messages->count()) {
                $notification = $this->setNotification();
                $this->send($notification);
            }
        } catch (Throwable $e) {
            Log::error(Logs::getErrorText($e));
        }
    }
}
