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
 * Year: 2026
 */

use App\Core\Environment\RuntimeEnvironment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Admin-editable values (gateways, mail, VAT...) move from .env to the
     * settings table so .env can be read-only after installation. The .env file
     * is not modified: its values stay as fallback and as a manual backup.
     */
    public function up(): void
    {
        $imported = RuntimeEnvironment::importFromEnvFile();
        if ($imported !== []) {
            Log::info('Runtime .env values copied to settings.', ['keys' => $imported]);
        }
    }

    /**
     * Nothing to undo: .env still holds the original values and the code of the
     * previous version reads them from there.
     */
    public function down(): void {}
};
