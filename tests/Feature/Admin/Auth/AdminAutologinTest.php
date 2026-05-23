<?php

namespace Tests\Feature\Admin\Auth;

use App\Models\Admin\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class AdminAutologinTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): Admin
    {
        return Admin::create([
            'username' => 'pentestadmin',
            'firstname' => 'Pentest',
            'lastname' => 'Admin',
            'email' => 'pentest@admin.local',
            'password' => bcrypt('password'),
            'role_id' => 1,
            'locale' => 'fr_FR',
        ]);
    }

    public function test_autologin_command_stores_hash_in_db_and_emits_plain_token_in_url(): void
    {
        $admin = $this->createAdmin();

        Artisan::call('clientxcms:admin-autologin', ['--email' => $admin->email, '--expire' => 5]);
        $output = Artisan::output();

        $this->assertMatchesRegularExpression('#Autologin link: https?://[^/]+/admin/autologin/\d+/[a-f0-9-]{36}\?signature=[a-f0-9]+#', $output);

        preg_match('#/admin/autologin/\d+/([a-f0-9-]{36})\?#', $output, $matches);
        $plainTokenInUrl = $matches[1] ?? null;
        $this->assertNotNull($plainTokenInUrl);
        $this->assertSame(36, strlen($plainTokenInUrl), 'URL token must be a 36-char UUID, not a 64-char hash');

        $admin->refresh();
        $stored = $admin->getMetadata('autologin_key');
        $this->assertSame(64, strlen($stored), 'Stored key must be a 64-char sha256 hash');
        $this->assertNotSame($plainTokenInUrl, $stored, 'Stored value must differ from URL token');
        $this->assertSame(hash('sha256', $plainTokenInUrl), $stored, 'Stored value must be sha256(url_token)');
    }

    public function test_autologin_link_with_correct_token_authenticates_admin(): void
    {
        $admin = $this->createAdmin();
        Artisan::call('clientxcms:admin-autologin', ['--email' => $admin->email, '--expire' => 5]);
        $output = Artisan::output();
        preg_match('#(/admin/autologin/\d+/[a-f0-9-]{36}\?signature=[a-f0-9]+)#', $output, $matches);
        $relativePath = $matches[1] ?? null;
        $this->assertNotNull($relativePath);

        $response = $this->get($relativePath);
        $response->assertRedirect(route('admin.dashboard'));
        $this->assertTrue(auth('admin')->check());
        $this->assertSame($admin->id, auth('admin')->id());
    }

    public function test_autologin_link_with_tampered_token_is_rejected(): void
    {
        $admin = $this->createAdmin();
        Artisan::call('clientxcms:admin-autologin', ['--email' => $admin->email, '--expire' => 5]);
        $output = Artisan::output();
        preg_match('#(/admin/autologin/\d+/)([a-f0-9-]{36})(\?signature=[a-f0-9]+)#', $output, $matches);
        $tamperedPath = $matches[1].'00000000-0000-0000-0000-000000000000'.$matches[3];

        $response = $this->get($tamperedPath);
        $response->assertRedirect(route('admin.login'));
        $this->assertFalse(auth('admin')->check());
    }

    public function test_autologin_link_with_db_leaked_hash_in_url_is_rejected(): void
    {
        $admin = $this->createAdmin();
        Artisan::call('clientxcms:admin-autologin', ['--email' => $admin->email, '--expire' => 5]);
        $admin->refresh();
        $stolenHashFromDb = $admin->getMetadata('autologin_key');

        $forged = '/admin/autologin/'.$admin->id.'/'.$stolenHashFromDb.'?signature=anything';

        $response = $this->get($forged);
        $response->assertRedirect(route('admin.login'));
        $this->assertFalse(auth('admin')->check());
    }
}
