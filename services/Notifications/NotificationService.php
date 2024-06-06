<?php

namespace App\Services\Notifications;

use App\Http\Models\Notifications;

class NotificationService
{
    private array $data;
    private string $action;

    public function __construct(array $data, string $action)
    {
        $this->data = $data;
        $this->action = $action;
    }

    public function saveResponse(): void
    {
        Notifications::saveResponse($this->data, $this->action);
    }
}
