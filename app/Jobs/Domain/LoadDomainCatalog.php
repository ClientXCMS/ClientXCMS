<?php

namespace App\Jobs\Domain;

use App\Models\Provisioning\Server;
use App\Models\Store\DomainOperation;
use App\Services\Domain\DomainCatalogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Throwable;

class LoadDomainCatalog implements ShouldQueue
{
    use Dispatchable, Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public string $operationId) {}

    public function handle(DomainCatalogService $catalog): void
    {
        \Cache::lock('domain-catalog:'.$this->operationId, 150)->block(1, function () use ($catalog) {
            $operation = DomainOperation::findOrFail($this->operationId);
            if (! in_array($operation->status, ['pending', 'loading'], true) || $operation->expires_at->isPast()) {
                return;
            }
            try {
                $server = Server::findOrFail($operation->server_id);
                if ($catalog->connectionKey($server) !== $operation->connection_key) {
                    throw new \RuntimeException('Registrar connection changed; reload the catalog');
                }
                $payload = $operation->payload;
                $offset = (int) $payload['offset'];
                $registrar = $catalog->registrar($server);
                $extensions = $payload['extensions'] ?? [];
                $page = $extensions !== [] && $registrar instanceof \App\Contracts\Domain\DomainCatalogFilterInterface
                    ? $registrar->catalogExtensions($server, $extensions)
                    : $registrar->catalogPage($server, $offset, 100);
                if ($page->nextOffset !== null && $page->nextOffset <= $offset) {
                    throw new \RuntimeException('Invalid catalog pagination');
                }
                foreach ($page->prices as $row) {
                    \Validator::make($row, ['extension' => ['required', 'regex:/^\.[a-z0-9-]+(?:\.[a-z0-9-]+)*$/', 'max:32'], 'action' => 'required|in:register,renew,transfer', 'billing' => 'required|in:annually,biennially,triennially', 'cost' => 'required|numeric|min:0|max:99999999', 'currency' => 'required|regex:/^[A-Z]{3}$/'])->validate();
                    $key = $row['extension'].'/'.$row['action'].'/'.$row['billing'].'/'.$row['currency'];
                    $payload['rows'][$key] = $row;
                }
                $payload['offset'] = $page->nextOffset ?? $offset;
                $operation->update(['status' => $page->nextOffset === null ? 'ready' : 'loading', 'payload' => $payload, 'progress' => $page->nextOffset === null ? 100 : ($page->total ? min(99, (int) ($page->nextOffset / $page->total * 100)) : 0)]);
            } catch (Throwable $e) {
                $operation->update(['status' => 'failed', 'error' => __('provisioning.admin.domain_tlds.tools.catalog_failed')]);
                report($e);
            }
        });
        // Dispatch outside the lock; also works with the sync queue in development.
        if (DomainOperation::find($this->operationId)?->status === 'loading') {
            self::dispatch($this->operationId);
        }
    }

    public function failed(?Throwable $exception): void
    {
        DomainOperation::whereKey($this->operationId)->whereIn('status', ['pending', 'loading'])->update(['status' => 'failed', 'error' => 'Catalog job failed; check the worker log']);
    }
}
