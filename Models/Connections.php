<?php

namespace Models;

use App\Http\Models\Accounts;
use App\Http\Models\Feeds;
use App\Http\Models\Orm;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Psr\Http\Message\ResponseInterface;

/**
 * @property integer $id
 * @property integer $account_id
 * @property string $link
 * @property string $hash
 * @property object $data
 * @property integer $products_restriction
 * @property Feeds $feed
 * @property Carbon $updated_at
 * @property Carbon $created_at
 * @property Accounts $account
 * @property string $local_state
 * @property string $prs_token
 */
class Connections extends Orm
{
    const API_ACTION_READ = 'read';
    const API_ACTION_UPDATE = 'update';
    const API_ACTION_CREATE = 'create';

    const API_ACTIONS = [
        self::API_ACTION_CREATE,
        self::API_ACTION_READ,
        self::API_ACTION_UPDATE,
    ];

    const REMOTE_STATE_ACTIVE = 'active';
    const REMOTE_STATE_INACTIVE = 'inactive';
    const REMOTE_STATE_ERROR = 'error';
    const REMOTE_STATES = [
        self::REMOTE_STATE_ACTIVE,
        self::REMOTE_STATE_INACTIVE,
        self::REMOTE_STATE_ERROR,
    ];

    const LOCAL_STATE_CONNECTED = 'connected';
    const LOCAL_STATE_WAITING = 'waiting';
    const LOCAL_STATE_FROZEN = 'frozen';

    const LOCAL_STATES = [
        self::LOCAL_STATE_CONNECTED,
        self::LOCAL_STATE_WAITING,
        self::LOCAL_STATE_FROZEN,
    ];

    const LOCAL_STATE_LABELS = [
        self::LOCAL_STATE_CONNECTED => 'Account connected',
        self::LOCAL_STATE_WAITING   => 'Waiting for approval',
        self::LOCAL_STATE_FROZEN    => 'Account frozen',
    ];

    protected $fillable = [
        'account_id',
        'link',
        'data',
        'hash',
        'remote_state',
        'local_state',
        'products_restriction',
        'created_at',
        'updated_at',
    ];

    protected $attributes = [
        'local_state' => self::LOCAL_STATE_WAITING,
    ];

    protected $casts = [
        'data' => 'json',
    ];

    public function feeds(): HasManyThrough
    {
        return $this->hasManyThrough(Feeds::class, Accounts::class, 'id', 'account_id', 'account_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Accounts::class, 'account_id');
    }

    public function setLocalState($state = null)
    {
        if (is_null($state)) {
            $this->local_state = $this->account_id ? self::LOCAL_STATE_CONNECTED : self::LOCAL_STATE_WAITING;
            return;
        }

        if (in_array($state, self::LOCAL_STATES)) {
            $this->local_state = $state;
        }
    }

    public static function getResponse($message, $status = 200, $data = []): array
    {
        $response = [
            'status'  => $status,
            'message' => $message,
        ];

        if (!empty($data)) {
            $response = array_merge($response, $data);
        }

        return $response;
    }

    public static function updateRemoteConnection(Connections $connection): bool
    {
        $state = 'Unknown error';
        if (array_key_exists($connection->local_state, Connections::LOCAL_STATE_LABELS)) {
            $state = Connections::LOCAL_STATE_LABELS[$connection->local_state];
        }

        return Connections::sendConnectionData($connection, Connections::API_ACTION_CREATE, [
            'prs_token'            => $connection->prs_token,
            'products_restriction' => $connection->products_restriction,
            'state'                => $state,
        ]);
    }

    public static function sendConnectionData(Connections $connection, $action, $params): bool
    {
        $link = self::getRequestLink($connection, $action);

        $client = new Client(['verify' => false]);
        $response = $client->post($link, [
            RequestOptions::JSON => array_merge([
                'plugin_token' => $connection->hash,
            ], $params),
        ]);

        return static::isRequestSuccess($response);
    }

    public static function getRequestLink(Connections $connection, string $action): string
    {
        $system = strtolower($connection->data['system']);
        return "$connection->link/$system/$action";
    }

    public static function getXmlLink(Connections $connection, $params = []): string
    {
        $params = array_merge($params, ['plugin_token' => $connection->hash]);
        return self::getRequestLink($connection, Connections::API_ACTION_READ) . '?' . http_build_query($params);
    }

    public static function isRequestSuccess(ResponseInterface $response): bool
    {
        return $response->getStatusCode() === 200;
    }
}
