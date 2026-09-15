<?php

namespace Tests\Fixtures;

use App\Core\NoneServerType;
use App\DTO\Provisioning\ConnectionResponse;
use GuzzleHttp\Psr7\Response;

// Lives outside the test class on purpose: Pint rewrites testConnection() to test_connection() inside test classes, which silently breaks the override.
class RecordingServerType extends NoneServerType
{
    public const UUID = 'recording_server_type';

    public array $received = [];

    protected string $uuid = self::UUID;

    public function testConnection(array $params): ConnectionResponse
    {
        $this->received = $params;

        return new ConnectionResponse(new Response(200, [], 'recorded'));
    }
}
