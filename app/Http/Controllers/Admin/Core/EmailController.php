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

namespace App\Http\Controllers\Admin\Core;

use App\Contracts\Store\ProductTypeInterface;
use App\Http\Controllers\Admin\AbstractCrudController;
use App\Jobs\MassEmailSendJob;
use App\Models\Account\Customer;
use App\Models\Account\EmailMessage;
use App\Models\Admin\Permission;
use App\Models\Provisioning\Server;
use App\Models\Provisioning\Service;
use App\Models\Store\Product;
use App\Notifications\CustomMail;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EmailController extends AbstractCrudController
{
    protected string $viewPath = 'admin.core.emails';

    protected string $routePath = 'admin.emails';

    protected string $model = EmailMessage::class;

    protected string $searchField = 'subject';

    protected string $translatePrefix = 'admin.emails';

    public function show(EmailMessage $email)
    {
        staff_aborts_permission(Permission::SHOW_EMAILS);

        return new Response($email->content, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    public function store(Request $request)
    {
        staff_aborts_permission(Permission::SEND_EMAILS);
        $validated = $request->validate([
            'subject' => 'required|max:255',
            'content' => 'required|string|max:65535',
            'button_text' => 'nullable|string|max:255',
            'button_url' => 'nullable|url|max:255',
            'condition' => 'nullable|string',
            'send_at' => 'nullable|date',
            'selected_emails' => 'required|string',
        ]);

        $emails = array_map('trim', explode(',', $request->input('selected_emails')));
        $emails = array_filter($emails, function ($email) {
            return filter_var($email, FILTER_VALIDATE_EMAIL);
        });

        if (empty($emails)) {
            return back()->with('error', __('admin.emails.no_valid_emails'));
        }

        $validEmails = Customer::whereIn('email', $emails)->pluck('email')->toArray();
        $invalidEmails = array_diff($emails, $validEmails);

        if (! empty($invalidEmails)) {
            return back()->with('error', __('admin.emails.invalid_emails', ['emails' => implode(', ', $invalidEmails)]));
        }

        $validated['selected_emails'] = implode(',', $validEmails);

        MassEmailSendJob::dispatch($validated)->delay(Carbon::parse($request->input('send_at')));

        return redirect()->route($this->routePath.'.index')->with('success', __($this->translatePrefix.'.success_job', ['count' => count($validEmails), 'date' => $request->input('send_at')]));
    }

    public function preview(Request $request)
    {
        staff_aborts_permission(Permission::SEND_EMAILS);
        $validated = $request->validate([
            'subject' => 'nullable',
            'content' => 'nullable|string|max:65535',
            'button_text' => 'nullable|string|max:255',
            'button_url' => 'nullable|url|max:255',
            'email' => 'required|email',
        ]);
        [$customer, $variables] = $this->generateVariables($validated['email']);

        return CustomMail::fromRequest($variables, $validated)->toMail($customer)->render();
    }

    public function search(Request $request)
    {
        $search = $request->query('q');

        return EmailMessage::where('subject', 'LIKE', "%{$search}%")->paginate($this->perPage);
    }

    public function destroy(EmailMessage $email)
    {
        staff_aborts_permission(Permission::MANAGE_EMAILS);
        $email->delete();

        return $this->deleteRedirect($email);
    }

    public function getCreateParams()
    {
        $servers = Server::all();
        $products = Product::all();
        $params = parent::getCreateParams();
        $params['step'] = \request()->has('condition') || \request()->has('emails') ? 2 : 1;
        $params['customer'] = Customer::first();
        if ($params['step'] == 1) {
            $params['conditions'] = $this->getConditions($servers, $products);
        } else {
            $params['selectedEmails'] = $this->selectEmails();
            $params['variables'] = $this->getVariables($servers, $products);
            $params['condition'] = \request()->query('condition', '');
            $params['item'] = new EmailMessage;
            if ($params['variables']->contains('%service_name%')) {
                $params['item']->button_url = route('front.services.show', ['service' => '%service_id%']);
            } else {
                $params['item']->button_url = route('home');
            }
            $params['item']->send_at = now()->addMinutes(10);
        }

        return $params;
    }

    private function getConditions(Collection $servers, Collection $products): array
    {
        $conditions = [
            'none' => __('global.none'),
            'all_customers' => __('admin.emails.all_customers'),
            'all_registered_customers' => __('admin.emails.all_registered_customers'),
            'customers_has_active_services' => __('admin.emails.customers_has_active_services'),
            'customers_has_no_services' => __('admin.emails.customers_has_no_services'),
            'old_customers' => __('admin.emails.old_customers'),
        ];
        foreach ($servers as $server) {
            $conditions['server_'.$server->id] = __('admin.emails.server', ['server' => $server->name]);
        }
        foreach ($products as $product) {
            $conditions['product_'.$product->id] = __('admin.emails.product', ['product' => $product->name]);
        }

        foreach ($products as $product) {
            $conditions['product_active_'.$product->id] = __('admin.emails.product_active', ['product' => $product->name]);
        }
        /** @var ProductTypeInterface[] $types */
        $types = app('extension')->getProductTypes();
        foreach ($types as $type) {
            $conditions['product_type_'.$type->uuid()] = __('admin.emails.product_type', ['type' => $type->title()]);
        }

        return $conditions;
    }

    private function getVariables(Collection $servers, Collection $products)
    {
        $condition = \request()->query('condition', '');
        $variables = collect(Customer::getNotificationContextVariables());
        if (str_starts_with($condition, 'server_')) {
            $variables = $variables->merge(Server::getNotificationContextVariables());
        }
        if (str_starts_with($condition, 'product_') || str_starts_with($condition, 'product_active_') || str_starts_with($condition, 'product_type_') || $condition == 'customers_has_active_services' || $condition == 'customers_has_no_services') {
            $variables = $variables->merge(Service::getNotificationContextVariables());
        }

        return $variables;
    }

    private function selectEmails()
    {
        $condition = \request()->query('condition', '');
        $defaultUsers = explode(',', \request()->query('emails'));
        foreach ($defaultUsers as $key => $email) {
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                unset($defaultUsers[$key]);
            }
            if (Customer::where('email', $email)->count() == 0) {
                unset($defaultUsers[$key]);
            }
            $defaultUsers[$key] = trim($email);
        }
        $emails = collect(Customer::whereIn('email', $defaultUsers)->select('id', 'email')->get());
        switch ($condition) {
            case 'all_customers':
                $ids = Service::all()->pluck('customer_id')->unique();
                $emails = $emails->merge(Customer::whereIn('id', $ids)->select('id', 'email')->get());
                break;
            case 'all_registered_customers':
                $emails = $emails->merge(Customer::whereNotNull('email')->select('id', 'email')->get());
                break;
            case 'customers_has_active_services':
                $ids = Service::where('status', Service::STATUS_ACTIVE)->pluck('customer_id')->unique();
                $emails = $emails->merge(Customer::whereIn('id', $ids)->select('id', 'email')->get());
                break;
            case 'customers_has_no_services':
                $ids = Service::all()->pluck('customer_id')->unique();
                $emails = $emails->merge(Customer::whereNotIn('id', $ids)->select('id', 'email')->get());
                break;
            case 'old_customers':
                $ids = Service::where('status', Service::STATUS_EXPIRED)->pluck('customer_id')->unique();
                $emails = $emails->merge(Customer::whereIn('id', $ids)->select('id', 'email')->get());
                break;
            case str_starts_with($condition, 'server_'):
                $serverId = str_replace('server_', '', $condition);
                $ids = Service::where('server_id', $serverId)->pluck('customer_id')->unique();
                $emails = $emails->merge(Customer::whereIn('id', $ids)->select('id', 'email')->get());
                break;
            case str_starts_with($condition, 'product_active_'):
                $productId = str_replace('product_active_', '', $condition);
                $ids = Service::where('product_id', $productId)->where('status', Service::STATUS_ACTIVE)->pluck('customer_id')->unique();
                $emails = $emails->merge(Customer::whereIn('id', $ids)->select('id', 'email')->get());
                break;
            case str_starts_with($condition, 'product_type_'):
                $type = str_replace('product_type_', '', $condition);
                $ids = Service::where('type', $type)->where('status', Service::STATUS_ACTIVE)->pluck('customer_id')->unique();
                $emails = $emails->merge(Customer::whereIn('id', $ids)->select('id', 'email')->get());
                break;
            case str_starts_with($condition, 'product_'):
                $productId = str_replace('product_', '', $condition);
                $ids = Service::where('product_id', $productId)->pluck('customer_id')->unique();
                $emails = $emails->merge(Customer::whereIn('id', $ids)->select('id', 'email')->get());
                break;
            default:
                break;
        }

        return $emails->map(function (Customer $customer) {
            return $customer->email;
        })->join(',');
    }

    private function generateVariables(string $email)
    {
        $customer = Customer::where('email', $email)->first();
        if ($customer == null) {
            return [new Customer, []];
        }

        $service = $customer->services()->whereNotNull('server_id')->whereNotNull('product_id')->first();
        if ($service == null) {
            return [$customer, $customer->getNotificationVariables()];
        }
        $server = $service->server;

        return [$customer, array_merge($customer->getNotificationVariables(), $server->getNotificationVariables(), $service->getNotificationVariables())];
    }
}
