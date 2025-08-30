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


namespace App\DTO\Admin\Dashboard;

use App\Models\Account\Customer;
use App\Models\Billing\Invoice;
use App\Models\Helpdesk\SupportTicket;
use App\Models\Provisioning\Server;
use App\Models\Provisioning\Service;
use App\Models\Store\Group;
use App\Models\Store\Product;
use Illuminate\Support\Collection;

class IntelligentSearchDTO
{
    private string $query;

    private Collection $items;

    public function __construct(string $query)
    {
        $this->query = $query;
        $this->items = collect();
        $this->searchCustomers();
        $this->searchServices();
        $this->searchInvoices();
        $this->searchSettings();
        $this->searchGroups();
        $this->searchProducts();
        $this->searchServers();
        $this->searchTickets();
    }

    public function toArray()
    {
        return $this->items->toArray();
    }

    private function searchCustomers()
    {
        if (! staff_has_permission('admin.show_customers')) {
            return;
        }
        // Search customers
        $customers = Customer::whereLike('firstname', $this->query)
            ->whereLike('lastname', $this->query)
            ->whereLike('email', $this->query)
            ->get();
        $this->items = $this->items->merge($customers->map(function ($customer) {
            return [
                'title' => __('global.customer').' '.$customer->fullName.' - '.$customer->email,
                'link' => route('admin.customers.show', $customer->id),
            ];
        }));
    }

    private function searchInvoices()
    {
        if (! staff_has_permission('admin.show_invoices')) {
            return;
        }
        // Search invoices
        $invoices = Invoice::whereLike('id', $this->query)
            ->orWhere('invoice_number', $this->query)
            ->get();
        $this->items = $this->items->merge($invoices->map(function ($invoice) {
            return [
                'title' => __('global.invoice').' '.$invoice->id.' - '.$invoice->invoice_number,
                'link' => route('admin.invoices.show', $invoice->id),
            ];
        }));
    }

    private function searchServices()
    {
        if (! staff_has_permission('admin.show_services')) {
            return;
        }
        // Search services
        $services = Service::whereLike('name', $this->query)
            ->orWhere('id', $this->query)
            ->get();
        $this->items = $this->items->merge($services->map(function ($service) {
            return [
                'title' => __('global.service').' '.$service->name.' - '.$service->id,
                'link' => route('admin.services.show', $service->id),
            ];
        }));
    }

    private function searchSettings()
    {
        $cards = app('settings')->getCards();
        foreach ($cards as $card) {
            foreach ($card->items as $item) {
                if (str_contains(__($item->name), $this->query)) {
                    $this->items->push([
                        'title' => __($item->name).' - '.__($card->name),
                        'link' => $item->url(),
                    ]);
                }
            }
        }
    }

    private function searchGroups()
    {
        if (! staff_has_permission('admin.manage_groups')) {
            return;
        }
        // Search groups
        $groups = Group::whereLike('name', $this->query)
            ->get();
        $this->items = $this->items->merge($groups->map(function ($group) {
            return [
                'title' => __('global.group').' '.$group->name.' - '.$group->id,
                'link' => route('admin.groups.show', $group->id),
            ];
        }));
    }

    private function searchProducts()
    {
        if (! staff_has_permission('admin.manage_products')) {
            return;
        }
        // Search products
        $products = Product::whereLike('name', $this->query)
            ->get();
        $this->items = $this->items->merge($products->map(function ($product) {
            return [
                'title' => __('global.product').' '.$product->name.' - '.$product->id,
                'link' => route('admin.products.show', $product->id),
            ];
        }));
    }

    private function searchServers()
    {
        if (! staff_has_permission('admin.manage_servers')) {
            return;
        }
        // Search servers
        $servers = Server::whereLike('name', $this->query)
            ->get();
        $this->items = $this->items->merge($servers->map(function ($server) {
            return [
                'title' => __('provisioning.server').' '.$server->name.' - '.$server->ip,
                'link' => route('admin.servers.show', $server->id),
            ];
        }));
    }

    private function searchTickets()
    {
        if (! staff_has_permission('admin.manage_tickets')) {
            return;
        }
        // Search tickets
        $tickets = SupportTicket::whereLike('subject', $this->query)
            ->get();
        $this->items = $this->items->merge($tickets->map(function ($ticket) {
            return [
                'title' => __('global.ticket').' '.$ticket->subject.' - '.$ticket->id,
                'link' => route('admin.helpdesk.tickets.show', $ticket->id),
            ];
        }));
    }
}
