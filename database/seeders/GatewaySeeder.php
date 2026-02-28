<?php

namespace Database\Seeders;

use App\Core\Gateway\BalanceType;
use App\Core\Gateway\BankTransfertType;
use App\Core\Gateway\MollieType;
use App\Core\Gateway\NoneGatewayType;
use App\Core\Gateway\PayPalExpressCheckoutType;
use App\Core\Gateway\PayPalMethodType;
use App\Core\Gateway\SquareType;
use App\Core\Gateway\StancerType;
use App\Core\Gateway\StripeType;
use App\Core\Gateway\SumUpType;
use App\Models\Billing\Gateway;
use App\Services\Core\PaymentTypeService;
use Illuminate\Database\Seeder;

class GatewaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Gateway::count() > 0) {
            $types = app(PaymentTypeService::class)->all()->keys()->toArray();
            foreach ($types as $uuid) {
                $first = Gateway::where('uuid', $uuid)->first();
                if ($first) {
                    $other = Gateway::where('uuid', $uuid)->where('id', '!=', $first->id)->get();
                    foreach ($other as $gateway) {
                        $gateway->delete();
                    }
                }
            }
        }
        $this->saveGateway(BankTransfertType::UUID, 'Virement Bancaire', 'unreferenced');
        $this->saveGateway(PayPalExpressCheckoutType::UUID, 'PayPal Express Checkout', 'active');
        $this->saveGateway(PayPalMethodType::UUID, 'PayPal', 'hidden');
        $this->saveGateway(BalanceType::UUID, 'Balance', 'active', 0);
        $this->saveGateway(StripeType::UUID, 'Carte bancaire', 'active');
        $this->saveGateway(StancerType::UUID, 'Stancer', 'hidden');
        $this->saveGateway(NoneGatewayType::UUID, 'Aucun', 'hidden', 0);
        $this->saveGateway(MollieType::UUID, 'Mollie', 'hidden');
        $this->saveGateway(SumUpType::UUID, 'SumUp', 'hidden');
        $this->saveGateway(SquareType::UUID, 'Square', 'hidden');
    }

    private function saveGateway(string $uuid, string $name, string $status, float $minimal_amount = 0.50)
    {
        if (Gateway::where('uuid', $uuid)->count() === 0) {
            Gateway::create([
                'name' => $name,
                'uuid' => $uuid,
                'status' => $status,
                'minimal_amount' => $minimal_amount,
            ]);
        }
    }
}
