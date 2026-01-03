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

namespace App\Core\Gateway;

use App\Abstracts\AbstractGatewayType;
use App\DTO\Core\Gateway\GatewayUriDTO;
use App\Models\Admin\Setting;
use App\Models\Billing\Gateway;
use App\Models\Billing\Invoice;
use Illuminate\Http\Request;

class BankTransfertType extends AbstractGatewayType
{
    const UUID = 'bank_transfert';

    protected string $name = 'Bank Transfert';

    protected string $uuid = self::UUID;

    protected string $image = 'bank-transfert-icon.png';

    protected string $icon = 'bi bi-bank';

    public function createPayment(Invoice $invoice, Gateway $gateway, Request $request, GatewayUriDTO $dto)
    {
        \Session::flash('success', __('client.invoices.banktransfer.flash'));

        return redirect($dto->returnUri);
    }

    public function processPayment(Invoice $invoice, Gateway $gateway, Request $request, GatewayUriDTO $dto)
    {
        // nothing to do
        return redirect()->route('front.invoices.show', $invoice);
    }

    public function validate(): array
    {
        return [
            'bank_transfert_details' => ['required', 'string', 'max:1000'],
        ];
    }

    public function configForm(array $context = [])
    {
        return view('admin.settings.store.gateways.bank-transfert', $context);
    }

    public function saveConfig(array $data)
    {
        Setting::updateSettings($data);
    }
}
