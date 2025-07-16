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
namespace App\Http\Controllers\Admin\Billing;

use App\Abstracts\PaymentMethodSourceDTO;
use App\Http\Controllers\Admin\AbstractCrudController;
use App\Models\Account\Customer;
use App\Models\Billing\Subscription;
use App\Models\Provisioning\Service;

class SubscriptionController extends AbstractCrudController
{
    protected string $model = Subscription::class;

    protected string $viewPath = 'admin.billing.subscriptions';

    protected string $routePath = 'admin.subscriptions';

    protected string $translatePrefix = 'billing.admin.subscriptions';

    protected ?string $managedPermission = 'admin.manage_invoices';

    public function getIndexFilters()
    {
        return [
            'active' => __('global.states.active'),
            'pending' => __('global.states.pending'),
            'cancelled' => __('global.states.cancelled'),
        ];
    }

    public function getCreateParams()
    {
        $data = parent::getCreateParams();
        $data['customers'] = Customer::all()->pluck('email', 'id')->toArray();
        $data['step'] = request()->has('customer_id') ? 2 : 1;
        if ($data['step'] == 2) {
            $customerId = (int) request('customer_id');
            $customer = Customer::find($customerId);
            if ($customer) {
                $data['services'] = Service::where('customer_id', $customerId)->where('status', 'active')->get()->pluck('name', 'id')->toArray();
                $data['paymentMethods'] = $this->paymentmethods($customer);
            } else {
                $data['step'] = 1;
            }
        }

        return $data;
    }

    private function paymentmethods(Customer $customer)
    {
        $paymentmethods = $customer->paymentMethods();
        if ($paymentmethods->isNotEmpty()) {
            $paymentmethods = collect(['default' => __('client.payment-methods.default')])->merge($paymentmethods->mapWithKeys(function (PaymentMethodSourceDTO $sourceDTO) {
                return [$sourceDTO->id => $sourceDTO->title()];
            }));
        }

        return $paymentmethods;
    }

    public function delete(Subscription $subscription)
    {
        $subscription->logs()->delete();
        $subscription->delete();

        return $this->deleteRedirect($subscription);
    }
}
