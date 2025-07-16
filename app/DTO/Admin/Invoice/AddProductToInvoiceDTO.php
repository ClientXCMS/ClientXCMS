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
namespace App\DTO\Admin\Invoice;

use App\Models\Billing\Invoice;
use App\Models\Store\Product;
use App\Services\Store\TaxesService;
use DragonCode\Contracts\Support\Arrayable;

class AddProductToInvoiceDTO implements Arrayable
{
    public Invoice $invoice;

    public Product $product;

    public int $quantity;

    public string $name;

    public ?string $description = null;

    public array $itemData;

    public float $unit_price_ht;

    public float $unit_price_ttc;

    public float $unit_setup_ht;

    public float $unit_setup_ttc;

    public function __construct(Invoice $invoice, Product $product, array $validatedData, array $itemData = [])
    {
        $this->invoice = $invoice;
        $this->product = $product;
        $this->unit_price_ht = $validatedData['unit_price_ht'];
        $this->unit_price_ttc = TaxesService::getPriceWithVat($this->unit_price_ht);
        $this->unit_setup_ht = $validatedData['unit_setup_ht'];
        $this->unit_setup_ttc = TaxesService::getPriceWithVat($this->unit_setup_ht);
        $this->quantity = $validatedData['quantity'];
        $this->name = $validatedData['name'];
        $this->description = $validatedData['description'] ?? '';
        $this->itemData = $itemData;
        if (array_key_exists('billing', $validatedData)) {
            $this->itemData['billing'] = $validatedData['billing'];
        }
    }

    public function toArray(): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'name' => $this->name,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit_price_ht' => $this->unit_price_ht,
            'unit_price_ttc' => $this->unit_price_ttc,
            'unit_setup_ht' => $this->unit_setup_ht,
            'unit_setup_ttc' => $this->unit_setup_ttc,
            'total' => $this->total(),
            'tax' => $this->tax(),
            'subtotal' => $this->subtotal(),
            'setupfee' => $this->setup(),
            'type' => 'service',
            'related_id' => $this->product->id,
            'data' => $this->itemData,
        ];
    }

    public function recurringPayment(bool $withQuantity = true)
    {
        if (! $withQuantity) {
            return $this->unit_price_ht;
        }

        return $this->unit_price_ht * $this->quantity;
    }

    public function setup(bool $withQuantity = true)
    {
        if (! $withQuantity) {
            return $this->unit_price_ht;
        }

        return $this->unit_price_ht * $this->quantity;
    }

    public function tax()
    {
        return TaxesService::getTaxAmount($this->subtotal(), tax_percent());
    }

    public function subtotal()
    {
        return $this->recurringPayment() + $this->setup();
    }

    public function total()
    {
        return $this->subtotal() + $this->tax();
    }
}
