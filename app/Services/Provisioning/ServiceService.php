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

namespace App\Services\Provisioning;

use App\DTO\Provisioning\ServiceStateChangeDTO;
use App\DTO\Store\UpgradeDTO;
use App\Exceptions\WrongPaymentException;
use App\Models\Billing\Invoice;
use App\Models\Provisioning\CancellationReason;
use App\Models\Provisioning\Service;
use App\Models\Provisioning\ServiceRenewals;
use App\Models\Store\Product;
use App\Services\Billing\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceService
{
    public static function changeServiceStatus(Request $request, Service $service, string $status): array
    {
        if ($status == 'unsuspend') {
            $result = $service->unsuspend();
        } elseif ($status == 'suspend') {
            $result = $service->suspend(! empty($request->get('reason')) ? $request->get('reason') : 'No reason provided', $request->has('notify'));
        } elseif ($status == 'expire') {
            $result = $service->expire(true);
            $status = 'terminate';
        } elseif ($status == 'cancel_delivery') {
            $invoice = $service->serviceRenewals->first();
            if ($invoice) {
                $invoice->invoice->cancel();
            }
            $result = $service->cancel('Cancelled by delivery', new \DateTime, true);
            $status = 'cancel';
        } elseif ($status == 'cancel') {
            if ($service->cancelled_at != null) {
                $result = $service->uncancel();
                $status = 'uncancel';
            } else {
                $reason = CancellationReason::find($request->get('reason'))->reason ?? 'No reason provided';
                $date = $request->expiration == 'end_of_period' ? $service->expires_at : new \DateTime;
                $result = $service->cancel($reason, $date, $request->get('expiration') == 'now');
            }
        } else {
            $result = new ServiceStateChangeDTO($service, false, 'Invalid status');
        }

        return [
            $result, $status,
        ];
    }

    /**
     * @param  Service  $service  - service to create invoice for
     * @param  string  $billing  - billing type
     * @param  string  $mode  - InvoiceService::CREATE_INVOICE or InvoiceService::APPEND_SERVICE
     * @param  int|null  $invoice_id  - append to existing invoice
     *
     * @throws \WrongPaymentException
     */
    /**
     * Idempotent renewal invoice creation.
     *
     * Hardened in v2.16 to fix the duplicate-pending-invoice bug:
     *   - A row-level lock on the service is taken inside a DB transaction.
     *   - We look up the latest non-renewed (=pending) ServiceRenewals row
     *     instead of trusting only $service->invoice_id, which is stale when
     *     a previous renewal was appended to a basket invoice rather than
     *     created standalone.
     *   - Same billing cycle requested → existing pending invoice is reused.
     *   - Different billing cycle → old pending invoice is cancelled, its
     *     ServiceRenewals row is soft-cancelled (releasing the partial unique
     *     index `service_renewals_pending_lock_unique`), then a fresh invoice
     *     is created.
     *   - The partial unique index guarantees no race condition can produce
     *     two pending renewals for the same service.
     *
     * @param  Service  $service  service to create invoice for
     * @param  string   $billing  billing cycle code (e.g. monthly, quarterly)
     * @param  string   $mode     InvoiceService::CREATE_INVOICE or APPEND_SERVICE
     * @param  int|null $invoice_id  append to existing invoice
     *
     * @throws WrongPaymentException
     */
    public static function createRenewalInvoice(Service $service, string $billing, string $mode = InvoiceService::CREATE_INVOICE, ?int $invoice_id = null): Invoice
    {
        if ($service->billing == 'onetime') {
            throw new WrongPaymentException('Cannot create invoice for onetime billing');
        }

        if (! in_array($mode, [InvoiceService::CREATE_INVOICE, InvoiceService::APPEND_SERVICE], true)) {
            throw new WrongPaymentException('Invalid mode for invoice creation');
        }

        return DB::transaction(function () use ($service, $billing, $mode, $invoice_id) {
            // Reload the service inside the transaction with FOR UPDATE so two
            // concurrent renew requests serialize through this critical section.
            /** @var Service $locked */
            $locked = Service::query()->whereKey($service->id)->lockForUpdate()->first();
            if ($locked === null) {
                throw new WrongPaymentException('Service not found while locking for renewal');
            }

            $existingInvoice = self::findReusablePendingInvoice($locked, $billing);
            if ($existingInvoice !== null) {
                // Idempotent path: keep the existing pending invoice + its
                // pending ServiceRenewals row.
                if ($locked->invoice_id !== $existingInvoice->id) {
                    $locked->update(['invoice_id' => $existingInvoice->id]);
                }

                return $existingInvoice;
            }

            // Either nothing pending, or pending but with a different billing
            // cycle. In the latter case findReusablePendingInvoice returned
            // null and we still must clean up.
            self::cancelStalePendingRenewals($locked, $billing);

            if ($mode === InvoiceService::CREATE_INVOICE) {
                $invoice = InvoiceService::createInvoiceFromService($locked, $billing);
                $locked->update(['invoice_id' => $invoice->id]);

                return $invoice;
            }

            // APPEND_SERVICE
            $invoice = Invoice::find($invoice_id);
            if ($invoice === null) {
                throw new WrongPaymentException('Invoice not found');
            }
            InvoiceService::appendServiceOnExistingInvoice($locked, $invoice, $billing);

            return $invoice;
        });
    }

    /**
     * Locate an open pending invoice we can safely reuse for the requested
     * billing cycle. Returns null if there is nothing pending, or if the
     * pending invoice targets a different billing cycle (caller is expected
     * to cancel it before issuing a new one).
     */
    private static function findReusablePendingInvoice(Service $service, string $requestedBilling): ?Invoice
    {
        $renewal = ServiceRenewals::query()
            ->where('service_id', $service->id)
            ->where('status', ServiceRenewals::STATUS_PENDING)
            ->whereNull('renewed_at')
            ->lockForUpdate()
            ->orderByDesc('id')
            ->first();

        if ($renewal === null) {
            return null;
        }

        /** @var Invoice|null $invoice */
        $invoice = Invoice::query()->whereKey($renewal->invoice_id)->lockForUpdate()->first();
        if ($invoice === null || ! $invoice->canPay()) {
            return null;
        }

        $existingItem = $invoice->items()
            ->where('type', 'renewal')
            ->where('related_id', $service->id)
            ->first();

        if ($existingItem === null) {
            return null;
        }

        $existingBilling = $existingItem->data['billing'] ?? null;

        return $existingBilling === $requestedBilling ? $invoice : null;
    }

    /**
     * Cancel any pending renewal row + its invoice for this service so a
     * fresh renewal can be created without tripping the partial unique
     * `pending_lock_key`. Safe to call when there is nothing pending.
     */
    private static function cancelStalePendingRenewals(Service $service, string $requestedBilling): void
    {
        $renewals = ServiceRenewals::query()
            ->where('service_id', $service->id)
            ->where('status', ServiceRenewals::STATUS_PENDING)
            ->whereNull('renewed_at')
            ->lockForUpdate()
            ->get();

        foreach ($renewals as $renewal) {
            $invoice = Invoice::find($renewal->invoice_id);
            if ($invoice !== null && $invoice->canPay()) {
                $invoice->cancel();
            }
            $renewal->status = ServiceRenewals::STATUS_CANCELLED;
            $renewal->save();
            $renewal->delete(); // soft delete → frees pending_lock_key
        }

        if ($service->invoice_id !== null) {
            $linked = Invoice::find($service->invoice_id);
            if ($linked === null || ! $linked->canPay()) {
                $service->update(['invoice_id' => null]);
            }
        }
    }

    /**
     * @return Service|Upgrade
     *
     * @throws \WrongPaymentException
     */
    public static function upgradeService(Service $service, Product $product, string $type)
    {
        if ($type == UpgradeDTO::MODE_INVOICE) {
            $invoice = InvoiceService::createInvoiceFromUpgrade($service, $product);

            return $invoice;
        } elseif ($type == UpgradeDTO::MODE_NO_INVOICE) {
            $dto = new UpgradeDTO($service);
            $upgrade = $dto->createUpgrade($product);
            $upgrade->deliver();

            return $upgrade;
        } else {
            throw new \WrongPaymentException('Invalid upgrade type');
        }
    }
}
