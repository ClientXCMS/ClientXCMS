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

namespace App\Models\Traits;

use App\Models\Admin\Admin;
use App\Notifications\CustomerBlockMail;

trait CanBlocked
{
    public function isBanned(): bool
    {
        if ($this->hasMetadata('banned')) {
            return true;
        }

        return false;
    }

    public function isSuspended(): bool
    {
        if ($this->hasMetadata('suspended')) {
            return true;
        }

        return false;
    }

    public function isBlocked()
    {
        return $this->isBanned() || $this->isSuspended();
    }

    public function ban(string $reason, bool $suspendServices = true, bool $notify = true, ?Admin $admin = null)
    {
        $admin = $admin ?? auth('admin')->user();
        $this->attachMetadata('banned', 'true');
        $this->attachMetadata('banned_reason', $reason);
        $this->attachMetadata('banned_at', now()->format('d/m/Y H:i'));
        if ($admin != null) {
            $this->attachMetadata('banned_by', $admin->username);
        }
        try {
            if ($suspendServices) {
                $servicesOnlines = $this->services()->where('status', 'active')->get();
                foreach ($servicesOnlines as $service) {
                    $service->suspend('Customer banned');
                }
            }
            if ($notify) {
                $this->notify(new CustomerBlockMail($reason, 'customer_banned'));
            }
        } catch (\Exception $e) {
            \Session::flash('error', $e->getMessage());
        }
    }

    public function suspend(string $reason, bool $suspendServices = true, bool $notify = true, ?Admin $admin = null)
    {
        $admin = $admin ?? auth('admin')->user();
        $this->attachMetadata('suspended', 'true');
        $this->attachMetadata('suspended_reason', $reason);
        $this->attachMetadata('suspended_at', now()->format('d/m/Y H:i'));
        if ($admin != null) {
            $this->attachMetadata('suspended_by', $admin->username);
        }

        try {
            if ($suspendServices) {
                $servicesOnlines = $this->services()->where('status', 'active')->get();
                foreach ($servicesOnlines as $service) {
                    $service->suspend('Customer suspended');
                }
            }
            if ($notify) {
                $this->notify(new CustomerBlockMail($reason, 'customer_suspended'));
            }
        } catch (\Exception $e) {
            \Session::flash('error', $e->getMessage());
        }
    }

    public function reactivate(bool $notify = true)
    {
        $this->detachMetadata('banned');
        $this->detachMetadata('banned_reason');
        $this->detachMetadata('banned_at');
        $this->detachMetadata('banned_by');
        $this->detachMetadata('suspended');
        $this->detachMetadata('suspended_reason');
        $this->detachMetadata('suspended_at');
        $this->detachMetadata('suspended_by');
        try {
            $servicesSuspended = $this->services()->where('status', 'suspended')->get();
            foreach ($servicesSuspended as $service) {
                $service->unsuspend();
            }
            if ($notify) {
                $this->notify(new CustomerBlockMail('', 'customer_reactivated'));
            }
        } catch (\Exception $e) {
            \Session::flash('error', $e->getMessage());
        }

    }

    public function getBlockedMessage()
    {
        if ($this->isBanned()) {
            return __('admin.customers.show.is_banned', ['reason' => $this->getMetadata('banned_reason'), 'username' => $this->getMetadata('banned_by'), 'date' => $this->getMetadata('banned_at')]);
        }
        if ($this->isSuspended()) {
            return __('admin.customers.show.is_suspended', ['reason' => $this->getMetadata('suspended_reason'), 'username' => $this->getMetadata('suspended_by'), 'date' => $this->getMetadata('suspended_at')]);
        }

        return null;
    }
}
