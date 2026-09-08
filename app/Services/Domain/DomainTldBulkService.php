<?php

namespace App\Services\Domain;

use App\Models\Provisioning\Server;
use App\Models\Store\DomainOperation;
use App\Models\Store\DomainTld;
use Illuminate\Validation\ValidationException;

class DomainTldBulkService
{
    public function fingerprint(DomainTld $tld): string
    {
        return hash('sha256', json_encode([$tld->fresh()->getAttributes(), $tld->prices()->orderBy('id')->get()->toArray()]));
    }

    public function priceMap(DomainTld $tld): array
    {
        $prices = [];
        foreach ($tld->prices as $price) {
            $prices[$price->currency][$price->action][$price->billing] = ['price' => $price->price, 'setup' => $price->setup];
        }

        return $prices;
    }

    public function apply(DomainOperation $operation, array $overrides = []): void
    {
        \DB::transaction(function () use ($operation, $overrides) {
            $operation = DomainOperation::whereKey($operation->id)->lockForUpdate()->firstOrFail();
            if ($operation->status === 'applied') {
                return;
            }
            if ($operation->status !== 'preview' || $operation->expires_at->isPast()) {
                $this->stale();
            }
            $payload = $operation->payload;
            if ($operation->server_id) {
                $server = Server::findOrFail($operation->server_id);
                if (app(DomainCatalogService::class)->connectionKey($server) !== $operation->connection_key) {
                    $this->stale();
                }
            }
            $count = 0;
            foreach ($payload['targets'] as $i => $target) {
                $tld = DomainTld::where('extension', $target['extension'])->lockForUpdate()->first();
                if ($target['before'] !== null) {
                    if (! $tld || $this->fingerprint($tld) !== $target['before']) {
                        $this->stale();
                    }
                } elseif ($tld) {
                    $this->stale();
                }
                $tld ??= new DomainTld(['extension' => $target['extension'], 'status' => 'hidden']);
                $tld->fill($target['settings']);
                $validated = app(DomainDefaultsService::class)->validate($tld->toArray(), $tld->server_id ? Server::find($tld->server_id) : null);
                $tld->fill(\Illuminate\Support\Arr::only($validated, DomainDefaultsService::FIELDS));
                if ($tld->status === 'active' && count($tld->default_nameservers ?? []) < 2) {
                    throw ValidationException::withMessages(['status' => __('provisioning.admin.domain_tlds.tools.missing_nameservers')]);
                }
                $tld->save();
                $prices = $target['prices'];
                foreach ($target['price_rows'] ?? [] as $row) {
                    $value = $overrides[$row['key']] ?? $row['selling'];
                    \Validator::make(['price' => $value], ['price' => 'required|numeric|min:0|max:99999999'])->validate();
                    $prices[$payload['currency']][$row['action']][$row['billing']]['price'] = $value;
                }
                app(DomainTldPriceWriter::class)->write($tld, $prices);
                $count++;
            }
            $operation->update(['status' => 'applied', 'progress' => 100, 'payload' => $payload + ['applied_count' => $count]]);
            \App\Models\ActionLog::log('domain_tlds.'.$operation->kind, DomainTld::class, null, $operation->admin_id, null, ['operation' => $operation->id, 'count' => $count]);
        });
    }

    private function stale(): never
    {
        throw ValidationException::withMessages(['operation' => __('provisioning.admin.domain_tlds.tools.stale')]);
    }
}
