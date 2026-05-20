<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Provisioning\Service;
use App\Services\Domain\DomainRegistrarManager;
use Illuminate\Http\Request;

class DomainManagementController extends Controller
{
    public function nameservers(Request $request, Service $service)
    {
        $this->authorizeService($service);
        $validated = $request->validate([
            'nameservers' => 'required|array|min:2|max:8',
            'nameservers.*' => 'required|string|max:253',
        ]);
        $result = app(DomainRegistrarManager::class)->fromService($service)->updateNameservers($service, $validated['nameservers']);

        return back()->with($result->success ? 'success' : 'error', $result->message);
    }

    public function storeDns(Request $request, Service $service)
    {
        $this->authorizeService($service);
        $validated = $request->validate([
            'type' => 'required|string|max:10',
            'name' => 'required|string|max:253',
            'value' => 'required|string|max:500',
            'ttl' => 'nullable|integer|min:60|max:86400',
        ]);
        $result = app(DomainRegistrarManager::class)->fromService($service)->createDnsRecord($service, $validated);

        return back()->with($result->success ? 'success' : 'error', $result->message);
    }

    public function destroyDns(Service $service, string $record)
    {
        $this->authorizeService($service);
        $result = app(DomainRegistrarManager::class)->fromService($service)->deleteDnsRecord($service, $record);

        return back()->with($result->success ? 'success' : 'error', $result->message);
    }

    private function authorizeService(Service $service): void
    {
        if (! auth('web')->user()->hasServicePermission($service, 'service.show') || $service->type !== 'domain') {
            abort(404);
        }
    }
}
