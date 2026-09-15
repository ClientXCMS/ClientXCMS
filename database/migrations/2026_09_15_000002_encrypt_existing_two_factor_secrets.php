<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('metadata')
            ->where('key', '2fa_secret')
            ->whereNotNull('value')
            ->where('value', '!=', '')
            ->orderBy('id')
            ->each(function ($row) {
                if ($this->alreadyEncrypted($row->value)) {
                    return;
                }

                DB::table('metadata')->where('id', $row->id)->update([
                    'value' => Crypt::encryptString($row->value),
                ]);
            });
    }

    public function down(): void
    {
        DB::table('metadata')
            ->where('key', '2fa_secret')
            ->whereNotNull('value')
            ->where('value', '!=', '')
            ->orderBy('id')
            ->each(function ($row) {
                try {
                    DB::table('metadata')->where('id', $row->id)->update([
                        'value' => Crypt::decryptString($row->value),
                    ]);
                } catch (\Throwable $e) {
                    // Already in clear, nothing to roll back for this one.
                }
            });
    }

    private function alreadyEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
};
