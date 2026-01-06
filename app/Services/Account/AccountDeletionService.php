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

namespace App\Services\Account;

use App\Models\Account\Customer;
use App\Models\ActionLog;
use App\Models\Provisioning\Service;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AccountDeletionService
{
    public function canDelete(Customer $customer): bool
    {
        return empty($this->getBlockingReasons($customer));
    }

    public function getBlockingReasons(Customer $customer): array
    {
        $reasons = [];

        $activeServices = $customer->services(true)
            ->whereIn('status', [
                Service::STATUS_ACTIVE,
                Service::STATUS_PENDING,
                Service::STATUS_SUSPENDED,
            ])
            ->get();

        if ($activeServices->isNotEmpty()) {
            $reasons['active_services'] = [
                'count' => $activeServices->count(),
                'services' => $activeServices->pluck('name', 'id')->toArray(),
            ];
        }

        $pendingInvoices = $customer->getPendingInvoices();
        if ($pendingInvoices->isNotEmpty()) {
            $reasons['pending_invoices'] = [
                'count' => $pendingInvoices->count(),
                'invoices' => $pendingInvoices->pluck('invoice_number', 'id')->toArray(),
            ];
        }

        return $reasons;
    }


    public function delete(Customer $customer, bool $force = false): bool
    {
        if (! $force && ! $this->canDelete($customer)) {
            throw new AccountDeletionException(
                __('client.profile.delete.has_blocking_reasons'),
                $this->getBlockingReasons($customer)
            );
        }

        return DB::transaction(function () use ($customer) {
            ActionLog::log(
                ActionLog::ACCOUNT_DELETED,
                Customer::class,
                $customer->id,
                auth('admin')->id(),
                $customer->id,
                ['email' => $customer->email, 'name' => $customer->fullName]
            );

            $customer->tickets()
                ->where('status', 'open')
                ->update([
                    'status' => 'closed',
                    'closed_at' => now(),
                ]);

            $customer->tickets()->update(['customer_id' => null]);

            $customer->invoices()->update(['customer_id' => null]);

            \App\Models\Store\CouponUsage::where('customer_id', $customer->id)
                ->update(['customer_id' => null]);
            $customer->customerNotes()->delete();
            if ($customer->twoFactorEnabled()) {
                $customer->twoFactorDisable();
            }
            Cache::forget('payment_methods_' . $customer->id);
            $customer->tokens()->delete();
            $customer->metadata()->delete();
            $customer->delete();

            return true;
        });
    }
    public function formatBlockingReasons(array $reasons): string
    {
        $messages = [];

        if (isset($reasons['active_services'])) {
            $messages[] = __('client.profile.delete.has_active_services', [
                'count' => $reasons['active_services']['count'],
            ]);
        }

        if (isset($reasons['pending_invoices'])) {
            $messages[] = __('client.profile.delete.has_pending_invoices', [
                'count' => $reasons['pending_invoices']['count'],
            ]);
        }

        return implode(' ', $messages);
    }
    public static function getDeletedUserPlaceholder(): string
    {
        return __('client.profile.delete.deleted_user_placeholder');
    }
}
