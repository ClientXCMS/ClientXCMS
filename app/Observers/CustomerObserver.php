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
 * Year: 2025
 */
namespace App\Observers;

use App\Models\Account\Customer;

class CustomerObserver
{
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
