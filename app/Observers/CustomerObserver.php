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

use App\Models\Account\Customer;
use App\Models\ActionLog;

class CustomerObserver
{

    public function updated(Customer $customer)
    {
        if ($customer->isDirty('balance') && auth('admin')->check()) {
            $old = $customer->getOriginal('balance');
            $reason = strtolower(__('global.by')) . ' ' . auth('admin')->user()->username;
            ActionLog::log(ActionLog::BALANCE_CHANGED, Customer::class, $customer->id, auth('admin')->id(), $customer->id, ['old' => formatted_price($old), 'new' => formatted_price($customer->balance), 'reason' => $reason], ['balance' => $old], ['balance' => $customer->balance]);
        }
    }
    public function deleting(Customer $customer)
    {
        $customer->services()->delete();
        $customer->invoices()->delete();
        $customer->emails()->delete();
        $customer->tickets()->delete();
        $customer->getLogsAction()->delete();
        $customer->update([
            'email' => 'deleted-'.$customer->id.'@clientxcms.com',
            'phone' => null,
            'address' => 'Deleted',
            'address2' => 'Deleted',
            'city' => 'Deleted',
            'state' => 'Deleted',
            'zipcode' => '00000',
            'country' => 'Deleted',
            'notes' => 'Deleted',
            'password' => 'deleted',
            'locale' => 'en',
            'region' => 'Deleted',
            'email_verified_at' => null,
            'is_confirmed' => false,
            'dark_mode' => false,
            'last_login_at' => null,
            'last_login_ip' => null,
            'balance' => 0,
        ]);
    }
}
