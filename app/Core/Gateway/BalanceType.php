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
use App\Models\Billing\Gateway;
use App\Models\Billing\Invoice;
use Illuminate\Http\Request;
use Str;

class BalanceType extends AbstractGatewayType
{
    const UUID = 'balance';

    protected string $name = 'Balance';

    protected string $uuid = self::UUID;

    protected string $image = 'balance-icon.png';

    protected string $icon = 'bi bi-currency-dollar';

    public function createPayment(Invoice $invoice, Gateway $gateway, Request $request, GatewayUriDTO $dto)
    {
        $transactionId = 'ctx-'.Str::uuid();
        $invoice->update(['external_id' => $transactionId]);

        return redirect($dto->returnUri);
    }

    public function processPayment(Invoice $invoice, Gateway $gateway, Request $request, GatewayUriDTO $dto)
    {
        if ($invoice->total > $invoice->customer->balance) {
            $invoice->fail();
        } else {
            $invoice->customer->addFund(-$invoice->total, 'Payment for invoice #'.$invoice->id);
            $invoice->complete();
        }

        return redirect()->route('front.invoices.show', $invoice);
    }

    public function saveConfig(array $data) {}

    public function validate(): array
    {
        return [];
    }
}
