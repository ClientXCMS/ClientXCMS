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

namespace App\Models\Billing;

use App\Abstracts\SupportRelateItemTrait;
use App\Contracts\Helpdesk\SupportRelateItemInterface;
use App\Core\Gateway\NoneGatewayType;
use App\DTO\Admin\Invoice\AddProductToInvoiceDTO;
use App\Exceptions\WrongPaymentException;
use App\Helpers\Countries;
use App\Mail\Invoice\InvoiceCreatedEmail;
use App\Models\Account\Customer;
use App\Models\Billing\Traits\InvoiceStateTrait;
use App\Models\Provisioning\Service;
use App\Models\Store\Product;
use App\Models\Traits\HasMetadata;
use App\Models\Traits\Loggable;
use App\Services\Billing\InvoiceService;
use App\Services\Store\TaxesService;
use App\Theme\ThemeManager;
use Barryvdh\DomPDF\PDF;
use Database\Factories\Core\InvoiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * @OA\Schema (
 *     schema="Invoice",
 *     title="Invoice",
 *     description="A billing invoice issued to a customer",
 *     required={"customer_id", "total", "currency", "status"},
 *
 *     @OA\Property(property="id", type="integer", example=1001),
 *     @OA\Property(property="uuid", type="string", example="123e4567-e89b-12d3-a456-426614174000"),
 *     @OA\Property(property="customer_id", type="integer", example=5),
 *     @OA\Property(property="billing_address", type="json", example=""),
 *     @OA\Property(property="due_date", type="string", format="date-time", example="2024-05-15T00:00:00Z"),
 *     @OA\Property(property="total", type="number", format="float", example=99.99),
 *     @OA\Property(property="subtotal", type="number", format="float", example=83.33),
 *     @OA\Property(property="tax", type="number", format="float", example=16.66),
 *     @OA\Property(property="setupfees", type="number", format="float", example=10.00),
 *     @OA\Property(property="currency", type="string", example="EUR"),
 *     @OA\Property(property="status", type="string", example="pending"),
 *     @OA\Property(property="external_id", type="string", nullable=true, example="ext-123456"),
 *     @OA\Property(property="notes", type="string", nullable=true, example="Payment pending confirmation."),
 *     @OA\Property(property="paymethod", type="string", example="stripe"),
 *     @OA\Property(property="fees", type="number", format="float", example=2.50),
 *     @OA\Property(property="invoice_number", type="string", example="CTX-2024-05-0001"),
 *     @OA\Property(property="paid_at", type="string", format="date-time", nullable=true, example="2024-05-10T08:00:00Z"),
 *     @OA\Property(property="payment_method_id", type="string", nullable=true, example="pm_1J2Y3Z4A5B6C7D8E9F0G"),
 *     @OA\Property(property="balance", type="number", format="float", example=0.00),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-05-01T12:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-05-10T09:00:00Z"),
 *     @OA\Property(
 *         property="items",
 *         type="array",
 *
 *         @OA\Items(ref="#/components/schemas/InvoiceItem")
 *     ),
 *
 *     @OA\Property(
 *         property="customer",
 *         ref="#/components/schemas/Customer"
 *     )
 * )
 *
 * @property int $id
 * @property string|null $uuid
 * @property \Illuminate\Support\Carbon $due_date
 * @property int $customer_id
 * @property string|null $billing_address
 * @property float $total
 * @property float $subtotal
 * @property float $tax
 * @property float $setupfees
 * @property string $currency
 * @property string $status
 * @property string|null $payment_method_id
 * @property \Illuminate\Support\Carbon|null $paid_at
 * @property string|null $external_id
 * @property string $notes
 * @property string $paymethod
 * @property float $fees
 * @property string|null $invoice_number
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property float|null $balance
 * @property-read Customer $customer
 * @property-read \App\Models\Billing\Gateway|null $gateway
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Billing\InvoiceItem> $items
 * @property-read int|null $items_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Billing\InvoiceLog> $logs
 * @property-read int|null $logs_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Metadata> $metadata
 * @property-read int|null $metadata_count
 *
 * @method static \Database\Factories\Core\InvoiceFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereBillingAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereDueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereExternalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereFees($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereInvoiceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice wherePaidAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice wherePaymentMethodId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice wherePaymethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereSetupfees($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereTax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Invoice extends Model implements SupportRelateItemInterface
{
    use HasFactory, HasMetadata, InvoiceStateTrait, Loggable, softDeletes, SupportRelateItemTrait;

    const STATUS_PENDING = 'pending';

    const STATUS_PAID = 'paid';

    const STATUS_DRAFT = 'draft';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_REFUNDED = 'refunded';

    const STATUS_FAILED = 'failed';

    const FILTERS = [
        'all' => 'all',
        self::STATUS_PENDING => 'pending',
        self::STATUS_PAID => 'paid',
        self::STATUS_CANCELLED => 'cancelled',
        self::STATUS_REFUNDED => 'refunded',
        self::STATUS_FAILED => 'failed',
    ];

    protected $fillable = [
        'customer_id',
        'billing_address',
        'due_date',
        'total',
        'subtotal',
        'tax',
        'setupfees',
        'currency',
        'status',
        'external_id',
        'notes',
        'paymethod',
        'fees',
        'invoice_number',
        'paid_at',
        'uuid',
        'payment_method_id',
        'balance',
    ];

    protected $casts = [
        'discount' => 'array',
        'due_date' => 'datetime',
        'created_at' => 'datetime',
        'paid_at' => 'datetime',
        'billing_address' => 'array',
    ];

    protected $attributes = [
        'status' => 'pending',
        'notes' => '',
        'setupfees' => 0,
        'total' => 0,
        'subtotal' => 0,
        'tax' => 0,
        'balance' => 0,
    ];

    public static function boot()
    {
        parent::boot();
        static::observe(\App\Observers\InvoiceObserver::class);
    }

    public static function getStatuses()
    {
        return [
            self::STATUS_PENDING => __('global.states.pending'),
            self::STATUS_PAID => __('global.states.paid'),
            self::STATUS_CANCELLED => __('global.states.cancelled'),
            self::STATUS_REFUNDED => __('global.states.refunded'),
            self::STATUS_FAILED => __('global.states.failed'),
            self::STATUS_DRAFT => __('global.states.draft'),
        ];
    }

    public function isDraft()
    {
        return $this->status == self::STATUS_DRAFT;
    }

    public function gateway()
    {
        return $this->belongsTo(Gateway::class, 'paymethod', 'uuid')->withDefault(new NoneGatewayType);
    }

    public function addService(Service $service)
    {
        InvoiceService::appendServiceOnExistingInvoice($service, $this);
        if ($service->invoice_id != $this->id && $service->invoice_id != null) {
            $service->update(['invoice_id' => $this->id]);
            Invoice::find($service->invoice_id)->cancel();
        }
        $service->update(['invoice_id' => $this->id]);
    }

    public function addProduct(Product $product, array $validatedData, array $productData)
    {
        InvoiceService::appendProductOnExistingInvoice(new AddProductToInvoiceDTO($this, $product, $validatedData, $productData));
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    /**
     * Get the customer name or placeholder for deleted users.
     */
    public function getCustomerNameAttribute(): string
    {
        if ($this->customer_id === null || $this->customer === null) {
            return __('client.profile.delete.deleted_user_placeholder');
        }

        return $this->customer->fullName;
    }

    public function logs()
    {
        return $this->hasMany(InvoiceLog::class);
    }

    public function pay(Gateway $gateway, Request $request)
    {
        if ($this->total == 0) {
            if ($gateway->uuid != 'none') {
                $gateway = Gateway::where('uuid', 'none')->first();
            }
        }
        if ($gateway->minimal_amount > $this->total) {
            throw new WrongPaymentException(__('store.checkout.minimal_amount', ['amount' => $gateway->minimal_amount]));
        }
        InvoiceLog::log($this, InvoiceLog::PAY_INVOICE, ['gateway' => $gateway->uuid]);
        $this->update(['paymethod' => $gateway->uuid]);

        return $gateway->createPayment($this, $request);
    }

    public function identifier()
    {
        return $this->invoice_number;
    }

    public function canPay()
    {
        return $this->status == self::STATUS_PENDING ||
            $this->status == self::STATUS_FAILED;
    }

    public function canDelete()
    {
        return $this->status == self::STATUS_DRAFT || $this->status == self::STATUS_CANCELLED || $this->status == self::STATUS_PENDING;
    }

    public function download(): Response
    {
        if (Storage::disk('local')->exists($this->getPdfPath())) {
            return Storage::disk('local')->download($this->getPdfPath(), $this->identifier().'.pdf');
        }

        $pdf = $this->generatePdf();

        return $pdf->download($this->identifier().'.pdf');
    }

    public function invoiceOutput(): string
    {
        if (Storage::disk('local')->exists($this->getPdfPath())) {
            return Storage::disk('local')->get($this->getPdfPath());
        }

        $pdf = $this->generatePdf(false);

        return $pdf->output();
    }

    public function pdf(): Response
    {
        if (Storage::disk('local')->exists($this->getPdfPath())) {
            $fullPath = Storage::disk('local')->path($this->getPdfPath());

            return response()->file($fullPath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$this->identifier().'.pdf"',
            ]);
        }
        $pdf = $this->generatePdf(false);

        return $pdf->stream($this->identifier().'.pdf');
    }

    public function getPdfPath(): string
    {
        return 'invoices/'.$this->getPdfName();
    }

    public function getPdfName(): string
    {
        $date = $this->created_at ? $this->created_at : now();

        return $date->format('Y').'/'.$date->format('m').'/'.$this->invoice_number.'.pdf';
    }

    public function generatePdf(bool $save = true): PDF
    {
        $filename = 'invoices/'.$this->getPdfName();
        $domain = request()->getSchemeAndHttpHost();
        if (str_contains($domain, 'localhost')) {
            $logoSrc = '/'.setting('app_logo_text');
        } else {
            $logoSrc = $domain.setting('app_logo_text');
        }

        $primaryColor = ThemeManager::getColorsArray()['600'];
        $color = ThemeManager::getContrastColor($primaryColor);
        $pdf = \PDF::loadView('front.billing.invoices.pdf', [
            'invoice' => $this,
            'customer' => $this->customer,
            'color' => $color,
            'address' => $this->billing_address,
            'logoSrc' => $logoSrc,
            'primaryColor' => $primaryColor,
        ]);
        if ($save) {
            Storage::put($filename, $pdf->output());
        }

        return $pdf;
    }

    public function clearServiceAssociation()
    {
        $services = Service::where('invoice_id', $this->id)->get();
        foreach ($services as $service) {
            $service->update(['invoice_id' => null]);
        }
    }

    public function addCustomProduct(array $validatedData)
    {
        $id = CustomItem::create([
            'name' => $validatedData['name'],
            'description' => $validatedData['description'],
        ])->id;
        InvoiceItem::create([
            'name' => $validatedData['name'],
            'description' => $validatedData['description'],
            'quantity' => $validatedData['quantity'],
            'unit_price_ttc' => $validatedData['unit_price_ttc'],
            'unit_setup_ttc' => $validatedData['unit_setup_ttc'],
            'invoice_id' => $this->id,
            'type' => 'custom_item',
            'related_id' => $id,
            'data' => [],
            'unit_price_ht' => TaxesService::getPriceWithoutVat($validatedData['unit_price_ttc']),
            'unit_setup_ht' => TaxesService::getPriceWithoutVat($validatedData['unit_setup_ttc']),
        ]);
    }

    protected static function newFactory()
    {
        return InvoiceFactory::new();
    }

    public function recalculate()
    {
        $subtotal = 0;
        $setupfees = 0;
        /** @var InvoiceItem $item */
        foreach ($this->items as $item) {
            $subtotal += $item->price() - $item->discountTotal();
            $setupfees += $item->unit_setup_ht * $item->quantity;
        }
        $subtotal = $subtotal - $this->balance;
        $vat = TaxesService::getTaxAmount($subtotal, tax_percent());
        $this->total = $subtotal + $vat;
        $this->subtotal = $subtotal;
        $this->tax = $vat;
        $this->setupfees = $setupfees;
        $this->save();
    }

    public function getDiscountTotal()
    {
        $total = 0;
        foreach ($this->items as $item) {
            $total += $item->discountTotal();
        }

        return $total;
    }

    public function relatedName(): string
    {
        return __('global.invoice').' #'.Str::limit($this->uuid, 5).' - '.$this->total.' '.currency_symbol($this->currency);
    }

    public function notifyCustomer(string $class = InvoiceCreatedEmail::class)
    {
        if ($this->customer->email) {
            $this->customer->notify(new $class($this));
        }
        InvoiceLog::log($this, InvoiceLog::SEND_INVOICE);
    }

    public static function generateInvoiceNumber(?string $date = null, bool $creation = true, int $add = 1): string
    {
        $prefix = setting('billing_invoice_prefix', 'CTX');
        $key = $date ?? now()->format('Y-m');
        if ($creation && InvoiceService::getBillingType() == InvoiceService::PRO_FORMA) {
            $prefix = "$prefix-PROFORMA-".str_pad(Invoice::withTrashed()->where('invoice_number', 'like', $prefix.'-PROFORMA-'.$key.'%')->count() + $add, 4, '0', STR_PAD_LEFT);
        } else {
            $prefix = $prefix.'-'.$key.'-'.str_pad(Invoice::withTrashed()->where('invoice_number', 'like', $prefix.'-'.$key.'%')->count() + $add, 4, '0', STR_PAD_LEFT);
        }
        if (Invoice::withTrashed()->where('invoice_number', $prefix)->exists()) {
            return self::generateInvoiceNumber($date, $creation, $add + 1);
        }

        return $prefix;
    }

    public static function updateInvoicePrefix(string $new): void
    {
        $all = Invoice::withTrashed()->where('invoice_number', 'like', setting('billing_invoice_prefix', 'CTX').'%')->get();
        foreach ($all as $invoice) {
            $invoice->update(['invoice_number' => str_replace(setting('billing_invoice_prefix', 'CTX'), $new, $invoice->invoice_number)]);
        }
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $model = $this->where('uuid', $value)->first();
        if (! $model) {
            $model = $this->where('id', $value)->first();
        }

        return $model ?? abort(404);
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    public function addBalance(float $amount)
    {
        if ($amount <= 0 || ! $this->canPay()) {
            return;
        }
        if ($amount >= ($this->total - $this->balance)) {
            $amount = $this->total - $this->balance;
            $this->customer->addFund(-$amount, 'Invoice payment for '.$this->id);
            $this->update(['paymethod' => 'balance']);
            $this->complete();

            return;
        }
        $this->customer->addFund(-$amount, 'Invoice payment for '.$this->id);
        $this->balance = $amount;
        $this->save();
        $this->recalculate();
    }

    public function getBillingAddressAttribute(): array
    {
        $address = $this->getBillingAddressArray();
        $lines = [];
        if ($address['company_name'] != null && $address['company_name'] != '') {
            $lines[] = $address['company_name'];
        } else {
            if (! empty($address['firstname']) && ! empty($address['lastname'])) {
                $lines[] = $address['firstname'].' '.$address['lastname'];
            }
        }
        $lines[] = $address['email'];
        $lines[] = $address['address'].' '.($address['address2'] != null ? $address['address2'] : '');
        $lines[] = $address['region'].' '.$address['city'].' '.$address['zipcode'];
        $lines[] = Countries::names()[$address['country']] ?? $address['country'];
        if (! empty($address['billing_details'])) {
            $lines = array_merge($lines, explode(PHP_EOL, $address['billing_details']));
        }

        return $lines;
    }

    public function getBillingAddressArray(): array
    {
        if (empty($this->attributes['billing_address'])) {
            $address = $this->customer->generateBillingAddress();
            $this->save(['billing_address' => $address]);
        }
        $address = json_decode($this->attributes['billing_address'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $address = $this->customer->generateBillingAddress();
            $this->save(['billing_address' => $address]);
        }

        return $address;
    }
}
