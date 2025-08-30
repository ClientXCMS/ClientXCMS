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

use App\Abstracts\PaymentMethodSourceDTO;
use App\Contracts\Store\GatewayTypeInterface;
use App\Mail\Invoice\SubscriptionFailedEmail;
use App\Models\Account\Customer;
use App\Models\Provisioning\Service;
use App\Services\Billing\InvoiceService;
use App\Services\Core\PaymentTypeService;
use App\Services\Store\RecurringService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 
 *
 * @property int $id
 * @property int|null $customer_id
 * @property string $state
 * @property \Illuminate\Support\Carbon|null $cancelled_at
 * @property int $cycles
 * @property \Illuminate\Support\Carbon|null $last_payment_at
 * @property int|null $service_id
 * @property string|null $payment_method_id
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Customer|null $customer
 * @property-read Service|null $service
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereCancelledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereCycles($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereLastPaymentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription wherePaymentMethodId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereServiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription withoutTrashed()
 * @property int $billing_day
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereBillingDay($value)
 * @mixin \Eloquent
 */
class Subscription extends Model
{
    use HasFactory, softDeletes;

    const DEFAULT_PAYMENT_METHOD = 'default';

    protected $fillable = [
        'customer_id',
        'state',
        'cancelled_at',
        'service_id',
        'payment_method_id',
        'cycles',
        'last_payment_at',
        'billing_day',
    ];

    protected $attributes = [
        'state' => 'disabled',
        'cycles' => 0,
        'billing_day' => 5,
    ];

    protected $casts = [
        'cancelled_at' => 'datetime',
        'last_payment_at' => 'datetime',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class)->withTrashed();
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function logs()
    {
        return $this->hasMany(SubscriptionLog::class)->withTrashed();
    }

    public function isActive()
    {
        return $this->state === 'active';
    }

    public static function createOrUpdateForService(Service $service, string $paymentMethodId)
    {
        if ($service->subscription) {
            $service->subscription->payment_method_id = $paymentMethodId;
            $service->cancelled_at = null;
            $service->subscription->state = 'active';
            $service->subscription->save();

            return $service->subscription;
        }
        $subscription = new static;
        $subscription->service_id = $service->id;
        $service->cancelled_at = null;
        $subscription->customer_id = $service->customer_id;
        $subscription->payment_method_id = $paymentMethodId;
        $subscription->state = 'active';
        $subscription->billing_day = $subscription->getFirstBillingDay();
        $subscription->save();

        return $subscription;
    }

    public function cancel()
    {
        $this->state = 'cancelled';
        $this->cancelled_at = now();
        $this->save();
    }

    public static function findForService(Service $service)
    {
        return static::where('service_id', $service->id)->first();
    }

    public function tryRenew()
    {
        $service = $this->service;
        $invoice = $service->invoice;
        if (! $invoice) {
            $invoice = InvoiceService::createInvoiceFromService($service);
            $service->update(['invoice_id' => $invoice->id]);
        }
        if ($invoice->status == 'pending') {
            $source = $invoice->customer->getSourceById($this->payment_method_id);
            /** @var GatewayTypeInterface $gateway */
            $gateway = app(PaymentTypeService::class)->get($source->gateway_uuid);
            if (! $gateway) {
                throw new \Exception('No gateway found for service '.$service->id);
            }
            $result = $gateway->payInvoice($invoice, $source);
            if (! $result->success) {
                $this->notifyUser($source);
                throw new \Exception('Failed to pay invoice for service '.$service->id.'('.$result->invoice->id.') :'.$result->message);
            }
            if ($result->invoice->status == 'paid') {
                SubscriptionLog::insert([
                    'subscription_id' => $this->id,
                    'invoice_id' => $invoice->id,
                    'paid_at' => now(),
                    'start_date' => $service->expires_at,
                    'amount' => $invoice->subtotal,
                    'end_date' => app(RecurringService::class)->addFrom($service->expires_at, $service->billing),
                ]);
                $result->invoice->attachMetadata('subscription_id', $this->id);
                $result->invoice->update(['payment_method_id' => $this->payment_method_id, 'paymethod' => $source->gateway_uuid]);
                $this->last_payment_at = now();
                $this->cycles++;
                $this->save();
            }

            return $result;
        }
        throw new \Exception('The invoice for service '.$service->id.' is not pending ('.$invoice->status.')');
    }

    public function toggle()
    {
        if ($this->state == 'active') {
            $this->state = 'cancelled';
        } else {
            $this->state = 'active';
        }
    }

    private function notifyUser(PaymentMethodSourceDTO $sourceDTO)
    {
        $service = $this->service;
        $retry = $service->getMetadata('renewal_tries', 0) >= setting('max_subscription_tries') && setting('max_subscription_tries') > 0;
        $service->customer->notify(new SubscriptionFailedEmail($service->invoice, $this, $sourceDTO, $retry));
    }
    public function getNextPaymentDate(): ?string {
        if ($this->service && $this->service->expires_at instanceof Carbon) {
            $billingDay = $this->billing_day ?? 5;

            $now = Carbon::now();
            $expiration = $this->service->expires_at;
            $billingDate = $expiration->copy()->day(min($billingDay, $expiration->daysInMonth));
            if ($billingDate->lessThanOrEqualTo($now)) {
                $billingDate->addMonth();
            }
            if ($billingDate->greaterThan($expiration)) {
                return null;
            }
            return $billingDate->format('d/j');
        }

        return null;
    }

    public function setBillingDay(int $day): void
    {
        if ($day < 1 || $day > 28) {
            throw new \InvalidArgumentException('Billing day must be between 1 and 28.');
        }
        $this->billing_day = $day;
        $this->save();
    }

    public function isValidBillingDay(int $day): bool
    {
        if ($this->service && $this->service->expires_at instanceof Carbon) {
            // check if next $day of the month or next month if is pasted is valid between service expires_at
            $now = Carbon::now();
            $billingDate = $this->service->expires_at->copy()->day(min($day, $this->service->expires_at->daysInMonth));
            if ($billingDate->lessThanOrEqualTo($now)) {
                $billingDate->addMonth();
            }
            if ($billingDate->greaterThan($this->service->expires_at)) {
                return false;
            }
            return true;
        }

        return false;
    }

    public function getFirstBillingDay(): int
    {
        if ($this->service && $this->service->expires_at instanceof Carbon) {
            $now = Carbon::now();
            $billingDay = $this->billing_day ?? 5;
            while ($now->day < $billingDay || $now->day > $now->daysInMonth) {
                $billingDay--;
                if ($billingDay < 1) {
                    return 1;
                }
            }
            return $billingDay;
        }
        return 5;
    }

}
