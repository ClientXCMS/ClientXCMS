<?php

namespace Tests\Unit\Models\Admin;

use App\Models\ActionLog;
use App\Models\Admin\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ActionLogIgnoredAttributesTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_attribute_the_model_wants_out_of_the_trail_stays_out(): void
    {
        Setting::updateSettings(['app_license_refresh_token' => 'old-token', 'app_name' => 'Before'], null, false);
        Setting::updateSettings(['app_license_refresh_token' => 'new-token', 'app_name' => 'After']);

        $attributes = DB::table('action_log_entries')->pluck('attribute')->all();

        $this->assertNotContains('app_license_refresh_token', $attributes, 'an attribute listed as ignored must never reach the audit trail');
        $this->assertContains('app_name', $attributes, 'the rest of the change must still be traced');
    }

    public function test_the_ignored_value_is_nowhere_in_the_trail(): void
    {
        Setting::updateSettings(['app_license_refresh_token' => 'old-token', 'app_name' => 'Before'], null, false);
        Setting::updateSettings(['app_license_refresh_token' => 'super-secret-token', 'app_name' => 'After']);

        $trail = json_encode(DB::table('action_log_entries')->get());

        $this->assertStringNotContainsString('super-secret-token', (string) $trail);
    }

    public function test_a_model_without_the_list_still_records_its_changes(): void
    {
        $log = ActionLog::create([
            'customer_id' => null,
            'staff_id' => null,
            'action' => 'test_action',
            'model' => \App\Models\Provisioning\Server::class,
            'model_id' => null,
            'payload' => [],
        ]);

        $log->createEntries(['name' => 'before'], ['name' => 'after']);

        $this->assertSame(1, DB::table('action_log_entries')->where('action_log_id', $log->id)->count());
    }

    public function test_an_unknown_model_string_does_not_break_the_trail(): void
    {
        $log = ActionLog::create([
            'customer_id' => null,
            'staff_id' => null,
            'action' => 'test_action',
            'model' => 'App\\Models\\ThisClassDoesNotExist',
            'model_id' => null,
            'payload' => [],
        ]);

        $log->createEntries(['name' => 'before'], ['name' => 'after']);

        $this->assertSame(1, DB::table('action_log_entries')->where('action_log_id', $log->id)->count());
    }
}
