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

namespace App\Services\Account;

use App\Models\Account\Customer;
use App\Models\Account\CustomerAccountInvitation;
use App\Models\Billing\Subscription;
use App\Models\Billing\Upgrade;
use App\Models\Store\CouponUsage;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * v2.16 — Builds the customer's GDPR Article-20 data export.
 *
 * Output is a ZIP file containing:
 *   - manifest.json   (export metadata + locale + generated_at)
 *   - profile.json    (selected personal/account fields — no authentication secrets)
 *   - invoices.json   (list + totals)
 *   - invoices/*.pdf  (each invoice as PDF — generated on demand)
 *   - services.json
 *   - credit_notes.json, subscriptions.json, upgrades.json
 *   - tickets.json    (each ticket with messages + attachment metadata)
 *   - ticket attachment contents (under each ticket folder, when available)
 *   - emails.json, account_accesses.json, coupon_usages.json
 *   - api_tokens.json (token names only — no secrets)
 *
 * The ZIP is stored under `gdpr/{customer-uuid}/{random}.zip` on the
 * default storage disk; the controller serves it through a signed URL
 * with a 24-hour TTL so it never sits unprotected behind a guessable
 * filename.
 */
class GdprExportService
{
    public const STORAGE_DIR = 'gdpr';

    public function buildArchive(Customer $customer): string
    {
        $tmpDir = storage_path('app/'.self::STORAGE_DIR.'/'.$customer->id);
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }
        // GDPR storage limitation: the signed URL TTL is 24h, anything older
        // is unreachable and just retains PII on disk - delete it.
        $this->purgeStaleArchives($tmpDir);

        $filename = sprintf('export-%s-%s.zip', $customer->id, now()->format('YmdHis'));
        $zipPath = $tmpDir.'/'.$filename;

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Could not open ZIP archive for writing: '.$zipPath);
        }

        $zip->addFromString('manifest.json', $this->encode([
            'export_version' => 2,
            'generated_at' => now()->toIso8601String(),
            'app' => config('app.name'),
            'customer_id' => $customer->id,
            'locale' => $customer->locale,
            'files' => [
                'profile.json', 'invoices.json', 'credit_notes.json', 'services.json',
                'subscriptions.json', 'upgrades.json', 'tickets.json', 'emails.json',
                'account_accesses.json', 'coupon_usages.json', 'api_tokens.json',
            ],
        ]));

        $zip->addFromString('profile.json', $this->encode($this->profile($customer)));
        $zip->addFromString('invoices.json', $this->encode($this->invoices($customer)));
        $zip->addFromString('credit_notes.json', $this->encode($this->creditNotes($customer)));
        $zip->addFromString('services.json', $this->encode($this->services($customer)));
        $zip->addFromString('subscriptions.json', $this->encode($this->subscriptions($customer)));
        $zip->addFromString('upgrades.json', $this->encode($this->upgrades($customer)));
        $zip->addFromString('tickets.json', $this->encode($this->tickets($customer)));
        $zip->addFromString('emails.json', $this->encode($this->emails($customer)));
        $zip->addFromString('account_accesses.json', $this->encode($this->accountAccesses($customer)));
        $zip->addFromString('coupon_usages.json', $this->encode($this->couponUsages($customer)));
        $zip->addFromString('api_tokens.json', $this->encode($this->apiTokens($customer)));

        // Attach each invoice's PDF — generated on demand if missing.
        foreach ($customer->invoices ?? [] as $invoice) {
            try {
                $pdfBytes = $invoice->invoiceOutput();
                if ($pdfBytes !== '') {
                    // Sanitize the entry name: invoice_number is admin-influenced
                    // (prefix setting) and a stray '/' or '..' would let an
                    // extractor write outside the invoices/ folder.
                    $safeName = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) $invoice->invoice_number);
                    $zip->addFromString('invoices/'.$safeName.'.pdf', $pdfBytes);
                }
            } catch (\Throwable $e) {
                logger()->warning('gdpr.export.invoice_pdf_failed', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        foreach ($customer->tickets()->with('attachments')->get() as $ticket) {
            foreach ($ticket->attachments as $attachment) {
                if (! Storage::disk('local')->exists($attachment->path)) {
                    continue;
                }
                $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', basename((string) $attachment->filename));
                $zip->addFromString(
                    'tickets/'.$ticket->id.'/attachments/'.$attachment->id.'-'.$safeName,
                    Storage::disk('local')->get($attachment->path)
                );
            }
        }

        $zip->close();

        return self::STORAGE_DIR.'/'.$customer->id.'/'.$filename;
    }

    /**
     * Delete archives older than the signed-URL TTL (24h). Keeps the
     * gdpr/{id}/ folder free of stale PII bundles.
     */
    private function purgeStaleArchives(string $dir): void
    {
        $cutoff = now()->subDay()->getTimestamp();
        foreach (glob($dir.'/*.zip') ?: [] as $file) {
            if (@filemtime($file) < $cutoff) {
                @unlink($file);
            }
        }
    }

    /**
     * Translate the relative storage path returned by buildArchive()
     * into a 24-hour signed URL the customer can click.
     */
    public function signedUrl(string $relativePath): string
    {
        return \URL::temporarySignedRoute(
            'front.profile.export.download',
            now()->addDay(),
            ['path' => $relativePath]
        );
    }

    private function profile(Customer $c): array
    {
        $row = $c->only([
            'id',
            'firstname',
            'lastname',
            'email',
            'phone',
            'address',
            'address2',
            'city',
            'region',
            'country',
            'zipcode',
            'locale',
            'company_name',
            'billing_details',
            'balance',
            'created_at',
            'updated_at',
            'email_verified_at',
            'is_confirmed',
            'gdpr_compliment',
            'last_login',
            'last_ip',
        ]);
        $row['has_two_factor'] = $c->twoFactorEnabled();
        $row['history'] = $c->getLogsAction()->get()->map(fn ($h) => [
            'action' => $h->action,
            'description' => $h->description,
            'created_at' => $h->created_at,
        ])->all();

        return $row;
    }

    private function invoices(Customer $c): array
    {
        return $c->invoices()->get()->map(fn ($i) => [
            'id' => $i->id,
            'invoice_number' => $i->invoice_number,
            'status' => $i->status,
            'currency' => $i->currency,
            'total' => $i->total,
            'subtotal' => $i->subtotal,
            'tax' => $i->tax,
            'billing_address' => $i->billing_address,
            // Gateway identifier only; never expose the provider's payment-method token.
            'payment_gateway' => $i->paymethod,
            'paid_at' => $i->paid_at,
            'created_at' => $i->created_at,
            'items' => $i->items()->get()->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price_ht' => $item->unit_price_ht,
                'unit_price_ttc' => $item->unit_price_ttc,
                'tax' => $item->tax,
                'unit_setup_ht' => $item->unit_setup_ht,
                'unit_setup_ttc' => $item->unit_setup_ttc,
            ])->all(),
        ])->all();
    }

    private function creditNotes(Customer $c): array
    {
        return $c->creditNotes()->get()->map(fn ($note) => [
            'id' => $note->id,
            'credit_note_number' => $note->credit_note_number,
            'invoice_id' => $note->invoice_id,
            'reason' => $note->reason,
            'amount' => $note->amount,
            'tax' => $note->tax,
            'currency' => $note->currency,
            'created_at' => $note->created_at,
        ])->all();
    }

    private function services(Customer $c): array
    {
        return $c->services()->get()->map(fn ($s) => [
            'id' => $s->id,
            'uuid' => $s->uuid,
            'name' => $s->name,
            'status' => $s->status,
            'billing' => $s->billing,
            'expires_at' => $s->expires_at,
            'created_at' => $s->created_at,
        ])->all();
    }

    private function tickets(Customer $c): array
    {
        return $c->tickets()->with(['messages', 'attachments', 'department'])->get()->map(fn ($t) => [
            'id' => $t->id,
            'subject' => $t->subject,
            'status' => $t->status,
            'priority' => $t->priority,
            'department_id' => $t->department_id,
            'department' => $t->department?->name,
            'messages' => $t->messages->map(fn ($m) => [
                'author' => $m->isStaff() ? 'staff' : 'you',
                'message' => $m->message,
                'created_at' => $m->created_at,
            ])->all(),
            'attachments' => $t->attachments->map(fn ($a) => [
                'id' => $a->id,
                'message_id' => $a->message_id,
                'filename' => $a->filename,
                'mime' => $a->mime,
                'size' => $a->size,
                'created_at' => $a->created_at,
            ])->all(),
            'created_at' => $t->created_at,
        ])->all();
    }

    private function emails(Customer $c): array
    {
        return $c->emails()->get()->map(fn ($email) => [
            'id' => $email->id,
            'recipient' => $email->recipient,
            'subject' => $email->subject,
            'content' => $email->content,
            'read_at' => $email->read_at,
            'created_at' => $email->created_at,
        ])->all();
    }

    private function subscriptions(Customer $c): array
    {
        return Subscription::where('customer_id', $c->id)->get()->map(fn ($s) => [
            'id' => $s->id, 'service_id' => $s->service_id, 'state' => $s->state,
            'cycles' => $s->cycles, 'billing_day' => $s->billing_day,
            'last_payment_at' => $s->last_payment_at, 'cancelled_at' => $s->cancelled_at,
            'created_at' => $s->created_at,
        ])->all();
    }

    private function upgrades(Customer $c): array
    {
        return Upgrade::where('customer_id', $c->id)->get()->map(fn ($u) => [
            'id' => $u->id, 'service_id' => $u->service_id, 'invoice_id' => $u->invoice_id,
            'old_product_id' => $u->old_product_id, 'new_product_id' => $u->new_product_id,
            'completed' => $u->upgraded, 'created_at' => $u->created_at,
        ])->all();
    }

    private function accountAccesses(Customer $c): array
    {
        $map = fn ($a) => [
            'id' => $a->id, 'owner_customer_id' => $a->owner_customer_id,
            'sub_customer_id' => $a->sub_customer_id, 'permissions' => $a->permissions,
            'all_services' => $a->all_services, 'service_ids' => $a->services()->pluck('services.id')->all(),
            'created_at' => $a->created_at,
        ];

        return [
            'granted' => $c->ownedAccountAccesses()->get()->map($map)->all(),
            'received' => $c->receivedAccountAccesses()->get()->map($map)->all(),
            'invitations' => CustomerAccountInvitation::where('owner_customer_id', $c->id)->get()->map(fn ($i) => [
                'id' => $i->id, 'email' => $i->email, 'permissions' => $i->permissions,
                'all_services' => $i->all_services, 'expires_at' => $i->expires_at,
                'accepted_at' => $i->accepted_at, 'revoked_at' => $i->revoked_at,
                'service_ids' => $i->services()->pluck('services.id')->all(), 'created_at' => $i->created_at,
            ])->all(),
        ];
    }

    private function couponUsages(Customer $c): array
    {
        return CouponUsage::where('customer_id', $c->id)->get()->map(fn ($usage) => [
            'id' => $usage->id, 'coupon_id' => $usage->coupon_id,
            'amount' => $usage->amount, 'used_at' => $usage->used_at,
            'created_at' => $usage->created_at,
        ])->all();
    }

    private function apiTokens(Customer $c): array
    {
        if (! method_exists($c, 'tokens')) {
            return [];
        }

        return $c->tokens()->get()->map(fn ($t) => [
            'name' => $t->name,
            'created_at' => $t->created_at,
            'last_used_at' => $t->last_used_at,
        ])->all();
    }

    private function encode(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
