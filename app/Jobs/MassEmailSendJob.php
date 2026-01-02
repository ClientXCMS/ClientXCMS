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

namespace App\Jobs;

use App\Models\Account\Customer;
use App\Notifications\CustomMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MassEmailSendJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private array $params;

    /**
     * Create a new job instance.
     */
    public function __construct(array $params)
    {
        $this->params = $params;
        if (! isset($this->params['button_text'])) {
            $this->params['button_text'] = '';
        }
        if (! isset($this->params['button_url'])) {
            $this->params['button_url'] = '';
        }
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $condition = $this->params['condition'];
        $emails = explode(',', $this->params['selected_emails']);
        foreach ($emails as $email) {
            $customer = Customer::where('email', $email)->first();
            if (! $customer) {
                continue;
            }
            self::sendEmail($customer, $this->params, $condition);
        }
    }

    public static function sendEmail(Customer $customer, array $params, ?string $condition = null)
    {
        if (in_array($condition, ['all_customers', 'all_registered_customers', 'customers_has_active_services', 'customers_has_no_services', 'old_customers']) || $condition == null) {
            $variables = $customer->getNotificationVariables();
            $customer->notify(new CustomMail($variables, $params['content'], $params['button_text'], $params['button_url'], $params['subject']));
        }
        if (str_starts_with($condition, 'server_')) {
            $serverId = (int) str_replace('server_', '', $condition);
            $customer->services->where('server_id', $serverId)->where('status', 'active')->each(function ($service) use ($customer, $params) {
                $variables = $service->getNotificationVariables() + $customer->getNotificationVariables() + $service->server->getNotificationVariables();
                $customer->notify(new CustomMail($variables, $params['content'], $params['button_text'], $params['button_url'], $params['subject']));
            });
        }
        if (str_starts_with($condition, 'product_')) {
            $productId = (int) str_replace('product_', '', $condition);
            $customer->services->where('product_id', $productId)->each(function ($service) use ($customer, $params) {
                $variables = $service->getNotificationVariables() + $customer->getNotificationVariables();
                $customer->notify(new CustomMail($variables, $params['content'], $params['button_text'], $params['button_url'], $params['subject']));
            });
        }

        if (str_starts_with($condition, 'product_active_')) {
            $productId = (int) str_replace('product_active_', '', $condition);
            $customer->services->where('product_id', $productId)->where('status', 'active')->each(function ($service) use ($customer, $params) {
                $variables = $service->getNotificationVariables() + $customer->getNotificationVariables();
                $customer->notify(new CustomMail($variables, $params['content'], $params['button_text'], $params['button_url'], $params['subject']));
            });
        }

        if (str_starts_with($condition, 'product_type_')) {
            $productType = str_replace('product_type_', '', $condition);
            $customer->services->where('type', $productType)->each(function ($service) use ($customer, $params) {
                $variables = $service->getNotificationVariables() + $customer->getNotificationVariables();
                $customer->notify(new CustomMail($variables, $params['content'], $params['button_text'], $params['button_url'], $params['subject']));
            });
        }
    }
}
