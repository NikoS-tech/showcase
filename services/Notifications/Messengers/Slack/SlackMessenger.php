<?php

namespace App\Services\Notifications\Messengers\Slack;

use App\Http\Notifications\Errors;
use App\Services\Notifications\Messengers\Messenger;
use Illuminate\Notifications\Notification as Notifications;

abstract class SlackMessenger extends Messenger
{
    protected string $channel = 'slack';
    protected string $title = 'Error';

    protected function setNotification(): Notifications
    {
        $data = $this->prepareMessages()->toArray();
        return new Errors($data);
    }

    protected function prepareMessage(array $data, string $desc): string
    {
        $data = array_merge([
            'msg'    => 'No message',
            'count'  => 0,
            'ids'    => 'no data',
            'status' => 'unknown',
            'server' => 'unknown',
        ], $data);

        $message = $data['msg'] ?: 'No message';
        $server = $data['server'] ?: 'Unknown server';

        $ids = $data['ids'];
        if ($data['count'] > 1) {
            $ids = "$ids _count: {$data['count']}_";
        }

        return "*$desc!*\t IDs: $ids, _status: {$data['status']}_, _server: {$server}_ \n ```$message``` \n\n";
    }
}
