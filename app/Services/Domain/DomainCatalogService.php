<?php

namespace App\Services\Domain;

use App\Contracts\Domain\DomainCatalogInterface;
use App\Models\Provisioning\Server;
use App\Models\Store\DomainOperation;
use Illuminate\Validation\ValidationException;

class DomainCatalogService
{
    public function registrar(Server $server): DomainCatalogInterface
    {
        $registrar = app(DomainRegistrarManager::class)->all()->get($server->hostname);
        if ($server->type !== 'domain' || ! $registrar instanceof DomainCatalogInterface) {
            throw ValidationException::withMessages(['server_id' => __('provisioning.admin.domain_tlds.tools.unsupported')]);
        }

        return $registrar;
    }

    public function connectionKey(Server $server): string
    {
        return hash_hmac('sha256', json_encode([$server->id, $server->hostname, $server->username, $server->password, $server->ip, $server->hasMetadata('test_mode')]), (string) config('app.key'));
    }

    /** @param array<int, string> $extensions */
    public function start(Server $server, int $adminId, array $extensions): DomainOperation
    {
        $registrar = $this->registrar($server);
        if (! $registrar instanceof \App\Contracts\Domain\DomainCatalogFilterInterface) {
            throw ValidationException::withMessages(['extensions' => __('provisioning.admin.domain_tlds.tools.filtered_catalog_unsupported')]);
        }
        $extensions = collect($extensions)
            ->map(fn ($extension) => strtolower(ltrim(trim((string) $extension), '.')))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $key = $this->connectionKey($server);

        return \DB::transaction(function () use ($server, $adminId, $key, $extensions) {
            Server::whereKey($server->id)->lockForUpdate()->firstOrFail();
            $cached = DomainOperation::where('kind', 'catalog')->where('admin_id', $adminId)->where('connection_key', $key)->where('expires_at', '>', now())->whereIn('status', ['pending', 'loading', 'ready'])->latest()->get()
                ->first(fn (DomainOperation $operation) => ($operation->payload['extensions'] ?? []) === $extensions);
            if ($cached) {
                return $cached;
            }
            $operation = DomainOperation::create(['kind' => 'catalog', 'admin_id' => $adminId, 'server_id' => $server->id, 'connection_key' => $key, 'payload' => ['rows' => [], 'extensions' => $extensions, 'offset' => 0, 'environment' => $server->hasMetadata('test_mode') ? 'sandbox' : 'production'], 'expires_at' => now()->addHour()]);
            \App\Jobs\Domain\LoadDomainCatalog::dispatch($operation->id)->afterCommit();

            return $operation;
        });
    }

    public function sellingPrice(float $cost, float $percentage, float $fixed, float $exchangeRate): float
    {
        if (! is_finite($cost) || $cost < 0 || ! is_finite($percentage) || $percentage < 0 || ! is_finite($fixed) || $fixed < 0 || ! is_finite($exchangeRate) || $exchangeRate <= 0) {
            throw ValidationException::withMessages(['prices' => __('provisioning.admin.domain_tlds.tools.invalid_price')]);
        }
        $price = round($cost * $exchangeRate * (1 + $percentage / 100) + $fixed, 2);
        if ($price > 99999999) {
            throw ValidationException::withMessages(['prices' => __('provisioning.admin.domain_tlds.tools.invalid_price')]);
        }

        return $price;
    }
}
