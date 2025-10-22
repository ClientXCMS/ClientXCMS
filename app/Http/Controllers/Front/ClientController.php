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


namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Billing\Invoice;
use App\Models\Helpdesk\SupportTicket;
use App\Models\Provisioning\Service;
use App\Services\Store\GatewayService;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index()
    {
        $servicesCount = auth()->user()->services()->count();
        $invoicesCount = auth()->user()->invoices()->where('status', '!=', 'draft')->count();
        $pending = auth()->user()->invoices()->where('status', Invoice::STATUS_PENDING)->count();
        $ticketsCount = auth()->user()->tickets()->count();
        $services = auth()->user()->services()->orderBy('created_at', 'desc')->whereNot('status', Service::STATUS_HIDDEN)->limit(5)->get();
        $tickets = auth()->user()->tickets()->orderBy('created_at', 'desc')->limit(5)->get();
        $invoices = auth()->user()->invoices()->where('status', '!=', Invoice::STATUS_DRAFT)->orderBy('created_at', 'desc')->limit(5)->paginate();
        $serviceFilters = Service::FILTERS;
        $invoiceFilters = Invoice::FILTERS;
        $gateways = GatewayService::getAvailable();

        return view('front.client.index', compact('gateways', 'tickets', 'services', 'services', 'invoices', 'ticketsCount', 'pending', 'servicesCount', 'invoicesCount', 'serviceFilters', 'invoiceFilters'));
    }

    public function onboarding(Request $request)
    {
        $user = $request->user();
        if ($user->hasVerifiedEmail()) {
            return redirect()->route('front.client.index');
        }

        return view('front.auth.onboarding', ['email' => auth()->user()->email]);
    }
}
