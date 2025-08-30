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

use App\DTO\Core\Gateway\GatewayUriDTO;
use App\Models\Billing\Gateway;
use App\Models\Billing\Invoice;
use App\Services\Store\GatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NoneGatewayType extends \App\Abstracts\AbstractGatewayType
{
    const UUID = 'none';

    protected string $name = 'None';

    protected string $uuid = self::UUID;

    protected string $image = 'none.png';

    protected string $icon = 'bi bi-x-circle';

    public function createPayment(Invoice $invoice, Gateway $gateway, Request $request, GatewayUriDTO $dto)
    {
        $transactionId = 'none-'.Str::uuid();
        $invoice->update(['external_id' => $transactionId]);

        return redirect($dto->returnUri);
    }

    public function processPayment(Invoice $invoice, Gateway $gateway, Request $request, GatewayUriDTO $dto)
    {
        $invoice->complete();

        return redirect()->route('front.invoices.show', $invoice);
    }

    public function saveConfig(array $data)
    {
        Gateway::where('uuid', self::UUID)->update([
            'status' => 'hidden',
        ]);
        GatewayService::forgotAvailable();
    }
}
