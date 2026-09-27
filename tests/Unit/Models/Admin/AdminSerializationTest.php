<?php

namespace Tests\Unit\Models\Admin;

use App\Models\Admin\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSerializationTest extends TestCase
{
    use RefreshDatabase;

    private const PUBLIC_FIELDS = ['firstname', 'id', 'lastname', 'username'];

    private function staff(): Admin
    {
        $admin = Admin::factory()->create();
        $admin->forceFill(['last_login_ip' => '203.0.113.77', 'locale' => 'fr_FR'])->save();

        return $admin->fresh()->load('role');
    }

    public function test_to_array_only_keeps_the_public_identity(): void
    {
        $keys = array_keys($this->staff()->toArray());
        sort($keys);

        $this->assertSame(self::PUBLIC_FIELDS, $keys);
    }

    public function test_json_only_keeps_the_public_identity(): void
    {
        $staff = $this->staff();
        $json = $staff->toJson();

        $this->assertStringNotContainsString('203.0.113.77', $json);
        $this->assertStringNotContainsString($staff->email, $json);
        $this->assertStringContainsString($staff->username, $json);
    }
}
