<?php

namespace App\Core\Domain\Concerns;

use App\Models\Provisioning\Server;

trait ResolvesRegistrarServer
{
    abstract public function uuid(): string;

    /** Credentials are hidden from Server::toArray(), so they are read explicitly. */
    protected function serverParams(Server $server, array $extra = []): array
    {
        return array_merge($server->toArray(), [
            'username' => $server->username,
            'password' => $server->password,
        ], $extra);
    }

    protected function findDefaultServer(): ?Server
    {
        return Server::query()
            ->where('type', 'domain')
            ->where('status', 'active')
            ->where('hostname', $this->uuid())
            ->first();
    }
}
