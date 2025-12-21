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
    /**
     * Check if a customer account can be deleted.
     */
    public function canDelete(Customer $customer): bool
    {
        return empty($this->getBlockingReasons($customer));
    }

    /**
     * Get the list of reasons blocking account deletion.
     * 
     * @return array<string, mixed>
     */
    public function getBlockingReasons(Customer $customer): array
    {
        $reasons = [];

        // Check for active services
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

        // Check for pending invoices
        $pendingInvoices = $customer->getPendingInvoices();
        if ($pendingInvoices->isNotEmpty()) {
            $reasons['pending_invoices'] = [
                'count' => $pendingInvoices->count(),
                'invoices' => $pendingInvoices->pluck('invoice_number', 'id')->toArray(),
            ];
        }

        return $reasons;
    }

    /**
     * Delete a customer account.
     * 
     * This will:
     * - Close all open tickets
     * - Set customer_id to NULL on invoices
     * - Set customer_id to NULL on tickets
     * - Disable 2FA
     * - Clear payment method preferences
     * - Revoke all API tokens
     * - Soft delete the customer
     * 
     * @param Customer $customer The customer to delete
     * @param bool $force Force deletion even if blocking reasons exist (admin only)
     * @throws \Exception if the account cannot be deleted and force is false
     */
    public function delete(Customer $customer, bool $force = false): bool
    {
        if (!$force && !$this->canDelete($customer)) {
            throw new AccountDeletionException(
                __('client.profile.delete.has_blocking_reasons'),
                $this->getBlockingReasons($customer)
            );
        }

        return DB::transaction(function () use ($customer) {
            // Log the deletion action
            ActionLog::log(
                ActionLog::ACCOUNT_DELETED,
                Customer::class,
                $customer->id,
                auth('admin')->id(),
                $customer->id,
                ['email' => $customer->email, 'name' => $customer->fullName]
            );

            // Close all open support tickets
            $customer->tickets()
                ->where('status', 'open')
                ->update([
                    'status' => 'closed',
                    'closed_at' => now(),
                ]);

            // Set customer_id to NULL on tickets (keep for history)
            $customer->tickets()->update(['customer_id' => null]);

            // Set customer_id to NULL on invoices (keep for accounting)
            $customer->invoices()->update(['customer_id' => null]);

            // Set customer_id to NULL on coupon usages
            \App\Models\Store\CouponUsage::where('customer_id', $customer->id)
                ->update(['customer_id' => null]);

            // Delete customer notes (they are internal admin notes)
            $customer->customerNotes()->delete();

            // Disable 2FA if enabled
            if ($customer->twoFactorEnabled()) {
                $customer->twoFactorDisable();
            }

            // Clear payment method cache
            Cache::forget('payment_methods_' . $customer->id);

            // Revoke all API tokens
            $customer->tokens()->delete();

            // Clear all metadata
            $customer->metadata()->delete();

            // Soft delete the customer
            $customer->delete();

            return true;
        });
    }

    /**
     * Format blocking reasons as a human-readable message.
     */
    public function formatBlockingReasons(array $reasons): string
    {
        $messages = [];

        if (isset($reasons['active_services'])) {
            $messages[] = __('client.profile.delete.has_active_services', [
                'count' => $reasons['active_services']['count']
            ]);
        }

        if (isset($reasons['pending_invoices'])) {
            $messages[] = __('client.profile.delete.has_pending_invoices', [
                'count' => $reasons['pending_invoices']['count']
            ]);
        }

        return implode(' ', $messages);
    }

    /**
     * Get a placeholder name for deleted users.
     */
    public static function getDeletedUserPlaceholder(): string
    {
        return __('client.profile.delete.deleted_user_placeholder');
    }
}
