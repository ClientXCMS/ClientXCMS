<?php

use App\Models\Admin\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ((new Setting)->encrypt as $name) {
            $row = DB::table('settings')->where('name', $name)->first();
            if ($row === null || $row->value === null || $row->value === '') {
                continue;
            }
            if ($this->alreadyEncrypted($row->value)) {
                continue;
            }
            DB::table('settings')->where('name', $name)->update(['value' => encrypt($row->value, false)]);
        }

        \Cache::forget('settings');
    }

    public function down(): void
    {
        foreach ((new Setting)->encrypt as $name) {
            $row = DB::table('settings')->where('name', $name)->first();
            if ($row === null || $row->value === null || $row->value === '') {
                continue;
            }
            try {
                DB::table('settings')->where('name', $name)->update(['value' => decrypt($row->value, false)]);
            } catch (DecryptException) {
                // Already in clear, nothing to roll back for this one.
            }
        }

        \Cache::forget('settings');
    }

    private function alreadyEncrypted(string $value): bool
    {
        try {
            decrypt($value, false);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }
};
