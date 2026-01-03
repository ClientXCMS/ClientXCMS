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

namespace App\Observers;

use App\Models\Billing\Subscription;
use App\Models\Provisioning\Service;

class ServiceObserver
{
    public function updating(Service $model)
    {
        if ($model->isDirty('billing')) {
            event(new \App\Events\Core\Service\ServiceChangeBillingEvent($model, $model->billing));
        }
    }

    public function creating(Service $model)
    {
        $model->uuid = generate_uuid(Service::class);
    }

    public function created(Service $model)
    {
        $product = $model->product;
        if (! $product) {
            return;
        }
        if ($product->hasMetadata('disable_cancel')) {
            $model->attachMetadata('disable_cancel', $product->getMetadata('disable_cancel'));
        }
    }

    public function updated(Service $model)
    {
        if ($model->wasChanged('expires_at') && $model->expires_at != null) {
            $model->last_expires_at = $model->getOriginal('expires_at');
            $server = $model->productType()->server();
            try {
                if ($server != null) {
                    $server->onRenew($model);
                }
            } catch (\Exception $e) {
            }
            /** @var \App\Models\Provisioning\ConfigOptionService $options */
            $options = $model->configoptions;
            foreach ($options as $option) {
                if ($option->expires_at != null && $option->expires_at->format('Y-m-d') == $model->getOriginal('expires_at')->format('Y-m-d')) {
                    $option->renew($model->expires_at->format('d/m/y H:i:s'));
                }
            }
        }
        if ($model->wasChanged('status') && $model->status == 'active') {
            if ($model->cancelled_at != null) {
                $model->cancelled_at = null;
                $model->cancelled_reason = null;
                $model->save();
            }
            //            $server = $model->productType()->server();
            //            try {
            //                if ($server != null) {
            //                    $server->unsuspendAccount($model);
            //                }
            //            } catch (\Exception $e) {
            //            }
        }

        if ($model->status == 'active' && $model->isDirty('status')) {
            //            try {
            //                $server = $model->productType()->server();
            //                if ($server != null) {
            //                    $server->unsuspendAccount($model);
            //                }
            //            } catch (\Exception $e) {
            //            }
        }
        if ($model->status == 'cancelled' && $model->isDirty('status') && $model->cancelled_at == null) {
            $model->cancelled_at = now();
            $model->save();
            if (Subscription::where('service_id', $model->id)->whereNull('cancelled_at')->exists()) {
                Subscription::where('service_id', $model->id)->cancel();
            }
        }
        if ($model->wasChanged('billing') && $model->billing == 'onetime' && $model->expires_at != null) {
            $model->expires_at = null;
            $model->save();
        }
    }

    public function deleting(Service $model)
    {
        $invoice = $model->invoice;
        if ($invoice != null) {
            $active = setting('remove_pending_invoice_type', 'cancel');
            if ($active === 'delete') {
                $invoice->items()->delete();
                $invoice->delete();
            } else {
                $invoice->cancel();
            }
        }
        $model->configoptions()->delete();
        $model->serviceRenewals()->delete();
        if (Subscription::where('service_id', $model->id)->whereNull('cancelled_at')->exists()) {
            Subscription::where('service_id', $model->id)->cancel();
        }
    }
}
