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

use App\Models\Admin\EmailTemplate;
use App\Services\Mail\StoredTemplateMigrator;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    private const BACKUP_PATH = 'mail-templates-before-grammar-migration.json';

    /**
     * Rewrites stored templates so the data reaches the new renderer at the same
     * time as the code that needs it. An operator who upgrades without running
     * anything by hand still gets working mail.
     *
     * What it cannot translate is left exactly as it was and shows up in
     * `php artisan content:audit`.
     */
    public function up(): void
    {
        $this->backup();

        $result = app(StoredTemplateMigrator::class)->migrate();

        Log::info('Mail templates converted to the closed grammar.', [
            'fields_rewritten' => $result['changed'],
            'left_for_review' => count($result['pending']),
            'backup' => self::BACKUP_PATH,
        ]);
    }

    /**
     * Deliberately empty. A converted template cannot be turned back: nothing in
     * `{{#invoice.items}}` says the loop variable used to be called `$item`.
     * The pre-migration content is in the backup file named above, which is why
     * the backup is written before anything is touched.
     */
    public function down(): void
    {
        Log::warning('Mail template conversion is not reversible; restore from '.self::BACKUP_PATH.' if needed.');
    }

    private function backup(): void
    {
        $templates = EmailTemplate::query()
            ->get(['id', 'name', 'locale', 'subject', 'content'])
            ->toArray();

        Storage::disk('local')->put(self::BACKUP_PATH, json_encode($templates, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
};
