<?php

/*
 * This file is part of the CLIENTXCMS project.
 * It is the property of the CLIENTXCMS association.
 *
 * Personal and non-commercial use of this source code is permitted.
 * However, any use in a project that generates profit (directly or indirectly),
 * or any reuse for commercial purposes, requires prior authorization from CLIENTXCMS.
 *
 * To request permission or for more information, please contact our support:
 * https://clientxcms.com/client/support
 *
 * Learn more about CLIENTXCMS License at:
 * https://clientxcms.com/eula
 *
 * Year: 2025
 */

namespace App\Services;

use App\Models\Account\Customer;
use App\Models\Admin\Admin;
use App\Models\Admin\Setting;
use App\Models\Billing\Invoice;
use App\Models\Helpdesk\SupportTicket;
use App\Models\Provisioning\Server;
use App\Providers\AppServiceProvider;

class TelemetryService
{
    const TELEMETRY_ENDPOINT = 'http://telemetry.clientxcms.com:8081/ping';

    /**
     * Send telemetry data to the server.
     *
     * @param  array  $data
     */
    public function sendTelemetry(): bool
    {
        if (env('TELEMETRY_ENABLED', 'true') == 'false') {
            return true;
        }
        $response = \Http::post(self::TELEMETRY_ENDPOINT, $this->getTelemetryData());

        return $response->successful();
    }

    private function getHostingType()
    {
        if (str_contains(base_path(), 'httpdocs')) {
            return 'plesk';
        }
        if (str_contains(base_path(), 'public_html')) {
            return 'cpanel';
        }
        try {
            if (app('license')->getLicense()->getServer() != null) {
                return 'cloud';
            }
        } catch (\Exception $e) {

        }
        if (str_contains(base_path(), 'var/www')) {
            return 'vps';
        }

        return 'unknown';
    }

    private function getInstallId()
    {
        $installId = setting('app_install_id');
        if (! $installId) {
            $bytes = random_bytes(12);
            $installId = rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
            $installId = substr($installId, 0, 16);
            Setting::updateSettings(['app_install_id' => $installId]);
        }

        return $installId;
    }

    private function getCreatedAt()
    {
        if (file_exists(storage_path('installed'))) {
            $installed = file_get_contents(storage_path('installed'));
            if ($installed) {
                $explode = explode(';time=', $installed);
                $createdAt = $explode[1] ?? null;
                if ($createdAt == null) {
                    $admin = Admin::whereNotNull('created_at')->first();
                    if ($admin && $admin->created_at) {
                        return $admin->created_at->format('Y-m-d H:i:s');
                    }
                }

                return date('Y-m-d H:i:s', (int) $createdAt);
            }
        }

        return date('Y-m-d H:i:s');
    }

    private function getTelemetryData()
    {
        return [
            'mode' => $this->getMode(),
            'hosting_type' => $this->getHostingType(),
            'cms_version' => AppServiceProvider::VERSION,
            'php_version' => phpversion(),
            'install_id' => $this->getInstallId(),
            'timestamp' => now()->toIso8601String(),
            'created_at' => $this->getCreatedAt(),
            'users' => Customer::count(),
            'customers' => Customer::sumCustomers(),
            'staffs' => Admin::count(),
            'invoices' => \App\Models\Billing\Invoice::count(),
            'tickets' => SupportTicket::count(),
            'invoices_paid' => \App\Models\Billing\Invoice::where('status', 'paid')->count(),
            'gateways' => (object) Invoice::groupBy('paymethod')->where('status', 'paid')->selectRaw('paymethod, count(*) as count')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->paymethod => $item->count];
                })->toArray(),
            'servers' => (object) Server::groupBy('type')->selectRaw('type, count(*) as count')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->type => $item->count];
                })->toArray(),
            'services' => (object) \App\Models\Provisioning\Service::groupBy('type')->selectRaw('type, count(*) as count')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->type => $item->count];
                })->toArray(),
            'extensions' => app('extension')->fetchEnabledExtensions(),
        ];
    }

    private function getMode()
    {
        try {
            $license = app('license')->getLicense();

            return 'entreprise';
        } catch (\Exception $e) {
            return 'inactive';
        }
    }
}
