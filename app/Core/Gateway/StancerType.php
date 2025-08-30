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
use App\Exceptions\WrongPaymentException;
use App\Helpers\EnvEditor;
use App\Models\Account\Customer;
use App\Models\Billing\Gateway;
use App\Models\Billing\Invoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Stancer\Config;
use Stancer\Payment;

class StancerType extends AbstractGatewayType
{
    const UUID = 'stancer';

    protected string $name = 'Stancer';

    protected string $uuid = self::UUID;

    protected string $image = 'stripe-icon.png';

    protected string $icon = 'bi bi-credit-card-2-front';

    public function createPayment(Invoice $invoice, Gateway $gateway, Request $request, GatewayUriDTO $dto)
    {
        if (env('STANCER_SECRET_KEY') === null || env('STANCER_PUBLIC_KEY') === null) {
            throw new WrongPaymentException('Stancer gateway is not configured.');
        }
        Config::init([
            'secret_key' => env('STANCER_SECRET_KEY'),
            'public_key' => env('STANCER_PUBLIC_KEY'),
        ]);
        $customer = $this->getCustomerStancer($invoice->customer);
        try {
            $payment = new Payment;
            $payment->setDescription(__('global.invoice').' '.$invoice->invoice_number);
            $payment->setCurrency($invoice->currency);
        } catch (\BadMethodCallException $e) {
            throw new WrongPaymentException($e->getMessage());
        }
        $payment->setAmount($invoice->total * 100);
        $payment->setCustomer($customer);
        $payment->setReturnUrl($dto->returnUri);
        $payment->setAuth(true);

        $paymentResponse = $payment->send();
        $invoice->update(['external_id' => $paymentResponse->getId()]);

        return new RedirectResponse($paymentResponse->getPaymentPageUrl());
    }

    private function getCustomerStancer(Customer $customer, bool $create = true): ?\Stancer\Customer
    {
        $stancerId = $customer->getMetadata($this->getEnvironmentFromKey().'_stancer_id');
        if ($stancerId !== null) {
            try {
                $stancerCustomer = new \Stancer\Customer($stancerId);

                return $stancerCustomer;
            } catch (\Stancer\Exceptions\NotFoundException $e) {

            }
        }
        if ($create) {
            $stancerCustomer = new \Stancer\Customer;
            $stancerCustomer->setEmail($customer->email);
            $stancerCustomer->setExternalId((string) $customer->id);
            $stancerCustomer->send();
            $customer->attachMetadata($this->getEnvironmentFromKey().'_stancer_id', $stancerCustomer->getId());
            $customer->save();

            return $stancerCustomer;
        }

        return null;
    }

    public function processPayment(Invoice $invoice, Gateway $gateway, Request $request, GatewayUriDTO $dto)
    {
        if (env('STANCER_SECRET_KEY') === null || env('STANCER_PUBLIC_KEY') === null) {
            throw new WrongPaymentException('Stancer gateway is not configured.');
        }
        Config::init([
            'secret_key' => env('STANCER_SECRET_KEY'),
            'public_key' => env('STANCER_PUBLIC_KEY'),
        ]);
        $paymentId = $invoice->external_id;

        try {
            $payment = new Payment($paymentId);
            if ($payment->isSuccess()) {
                $invoice->complete();
            } else {
                $invoice->fail();
            }

            return redirect()->route('front.invoices.show', $invoice);
        } catch (\BadMethodCallException $e) {
            throw new WrongPaymentException($e->getMessage());
        }
    }

    public function validate(): array
    {
        return [
            'private_key' => 'required|string',
            'public_key' => 'required|string',
        ];
    }

    public function configForm(array $context = [])
    {
        return view('admin.settings.store.gateways.stancer', $context);
    }

    public function saveConfig(array $data)
    {
        EnvEditor::updateEnv([
            'STANCER_SECRET_KEY' => $data['private_key'],
            'STANCER_PUBLIC_KEY' => $data['public_key'],
        ]);
    }

    private function getEnvironmentFromKey(): string
    {
        if (env('STANCER_SECRET_KEY') === null || env('STANCER_PUBLIC_KEY') === null) {
            throw new WrongPaymentException('Stancer gateway is not configured.');
        }

        return str_starts_with(env('STANCER_SECRET_KEY'), 'stest_') ? 'test' : 'live';
    }
}
