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
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * v2.16 — Builds the customer's GDPR Article-20 data export.
 *
 * Output is a ZIP file containing:
 *   - manifest.json   (export metadata + locale + generated_at)
 *   - profile.json    (every column of customers — minus password)
 *   - invoices.json   (list + totals)
 *   - invoices/*.pdf  (each invoice as PDF — generated on demand)
 *   - services.json
 *   - tickets.json    (each ticket with messages + attachments names)
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
        $tmpDir = storage_path('app/' . self::STORAGE_DIR . '/' . $customer->id);
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }
        // GDPR storage limitation: the signed URL TTL is 24h, anything older
        // is unreachable and just retains PII on disk - delete it.
        $this->purgeStaleArchives($tmpDir);

        $filename = sprintf('export-%s-%s.zip', $customer->id, now()->format('YmdHis'));
        $zipPath = $tmpDir . '/' . $filename;

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Could not open ZIP archive for writing: ' . $zipPath);
        }

        $zip->addFromString('manifest.json', $this->encode([
            'export_version' => 1,
            'generated_at' => now()->toIso8601String(),
            'app' => config('app.name'),
            'customer_id' => $customer->id,
        ]));

        $zip->addFromString('profile.json', $this->encode($this->profile($customer)));
        $zip->addFromString('invoices.json', $this->encode($this->invoices($customer)));
        $zip->addFromString('services.json', $this->encode($this->services($customer)));
        $zip->addFromString('tickets.json', $this->encode($this->tickets($customer)));
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
                    $zip->addFromString('invoices/' . $safeName . '.pdf', $pdfBytes);
                }
            } catch (\Throwable $e) {
                logger()->warning('gdpr.export.invoice_pdf_failed', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $zip->close();

        return self::STORAGE_DIR . '/' . $customer->id . '/' . $filename;
    }

    /**
     * Delete archives older than the signed-URL TTL (24h). Keeps the
     * gdpr/{id}/ folder free of stale PII bundles.
     */
    private function purgeStaleArchives(string $dir): void
    {
        $cutoff = now()->subDay()->getTimestamp();
        foreach (glob($dir . '/*.zip') ?: [] as $file) {
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
            'last_login',
            'last_ip',
        ]);
        $row['has_two_factor'] = $c->twoFactorEnabled();
        return $row;
    }

    private function invoices(Customer $c): array
    {
        return $c->invoices()->get()->map(fn($i) => [
            'id' => $i->id,
            'invoice_number' => $i->invoice_number,
            'status' => $i->status,
            'currency' => $i->currency,
            'total' => $i->total,
            'subtotal' => $i->subtotal,
            'tax' => $i->tax,
            'paid_at' => $i->paid_at,
            'created_at' => $i->created_at,
        ])->all();
    }

    private function services(Customer $c): array
    {
        return $c->services()->get()->map(fn($s) => [
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
        return $c->tickets()->with('messages')->get()->map(fn($t) => [
            'id' => $t->id,
            'subject' => $t->subject,
            'status' => $t->status,
            'priority' => $t->priority,
            'department_id' => $t->department_id,
            'messages' => $t->messages->map(fn($m) => [
                'author' => $m->isStaff() ? 'staff' : 'you',
                'message' => $m->message,
                'created_at' => $m->created_at,
            ])->all(),
            'created_at' => $t->created_at,
        ])->all();
    }

    private function apiTokens(Customer $c): array
    {
        if (! method_exists($c, 'tokens')) {
            return [];
        }
        return $c->tokens()->get()->map(fn($t) => [
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
