<?php

namespace Tests\Feature\Admin\Settings;

use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SettingsCoreControllerTest extends \Tests\TestCase
{
    use RefreshDatabase;
    
    public function test_show_email_settings(): void
    {
        $response = $this->performAdminAction('get', route('admin.settings.show', ['card' => 'core', 'uuid' => 'mail']));
        $response->assertStatus(200);
    }

    public function test_show_email_settings_without_permission(): void
    {
        $response = $this->performAdminAction('get', route('admin.settings.show', ['card' => 'core', 'uuid' => 'mail']), [], ['admin.manage_settings']);
        $response->assertStatus(403);
    }

    public function test_save_email_settings_without_permission(): void
    {
        $response = $this->performAdminAction('put', route('admin.settings.core.email'), [], ['admin.manage_settings']);
        $response->assertStatus(403);
    }

    public function test_save_email_settings_smtp(): void
    {
        $response = $this->performAdminAction('put', route('admin.settings.core.email'), [
            'mail_from_address' => 'test@test.com',
            'mail_from_name' => 'test',
            'mail_greeting' => 'test',
            'mail_salutation' => 'test',
            'mail_domain' => 'test',
            'mail_smtp_enable' => true,
            'mail_smtp_host' => 'test',
            'mail_smtp_port' => 465,
            'mail_smtp_username' => 'test',
            'mail_smtp_password' => 'test',
            'mail_smtp_encryption' => 'ssl',
        ]);
        $response->assertStatus(302);
        $this->assertEquals('test', setting('mail_smtp_username'));
    }

    public function test_show_core_settings(): void
    {
        $response = $this->performAdminAction('get', route('admin.settings.show', ['card' => 'core', 'uuid' => 'app']));
        $response->assertStatus(200);
    }

    public function test_show_core_settings_without_permission(): void
    {
        $response = $this->performAdminAction('get', route('admin.settings.show', ['card' => 'core', 'uuid' => 'app']), [], ['admin.manage_settings']);
        $response->assertStatus(403);
    }

    public function test_save_core_settings_without_permission(): void
    {
        $response = $this->performAdminAction('put', route('admin.settings.core.app'), [
            'app_name' => 'test',
            'app_env' => 'test',
            'app_debug' => true,
            'app_timezone' => 'test',
            'app_default_locale' => 'fr_FR',
            'app_logo' => UploadedFile::fake()->image('logo.png'),
        ], ['admin.manage_settings']);
        $response->assertStatus(403);
    }

    public function test_save_core_settings(): void
    {
        $response = $this->performAdminAction('put', route('admin.settings.core.app'), [
            'app_name' => 'test',
            'app_env' => 'test',
            'app_debug' => true,
            'app_timezone' => 'test',
            'app_default_locale' => 'fr_FR',
            'app_logo' => UploadedFile::fake()->image('logo.png'),
        ]);
        $this->assertEquals('test', setting('app_name'));
    }

    public function test_save_core_settings_changing_logo(): void
    {
        $response = $this->performAdminAction('put', route('admin.settings.core.app'), [
            'app_name' => 'test',
            'app_env' => 'test',
            'app_debug' => true,
            'app_timezone' => 'test',
            'app_default_locale' => 'fr_FR',
            'app_logo' => UploadedFile::fake()->image('logo.png'),
        ]);
        $response->assertStatus(302);
        $this->assertEquals('test', setting('app_name'));
        $response = $this->performAdminAction('put', route('admin.settings.core.app'), [
            'app_name' => 'test',
            'app_env' => 'test',
            'app_debug' => true,
            'app_timezone' => 'test',
            'app_default_locale' => 'fr_FR',
            'app_logo' => UploadedFile::fake()->image('logo2.png'),
        ]);
        $response->assertStatus(302);
        $this->assertFileDoesNotExist('storage/app/public/'.setting('app_logo'));
    }

    public function test_save_core_setting_with_remove_logo()
    {
        $response = $this->performAdminAction('put', route('admin.settings.core.app'), [
            'app_name' => 'test',
            'app_env' => 'test',
            'app_debug' => true,
            'app_timezone' => 'test',
            'app_default_locale' => 'fr_FR',
            'remove_logo' => true,
        ]);
        $response->assertStatus(302);
        $this->assertFileDoesNotExist('storage/app/public/'.setting('app_logo'));
    }

    public function test_show_maintenance_settings(): void
    {
        $response = $this->performAdminAction('get', route('admin.settings.show', ['card' => 'core', 'uuid' => 'maintenance']));
        $response->assertStatus(200);
    }

    public function test_save_maintenance_settings(): void
    {
        $response = $this->performAdminAction('put', route('admin.settings.core.maintenance'), [
            'maintenance_enabled' => true,
            'maintenance_url' => 'https://google.com',
            'maintenance_message' => 'test',
            'maintenance_button_text' => 'test',
            'maintenance_button_icon' => 'test',
            'maintenance_button_url' => 'https://google.com',
        ]);
        $response->assertStatus(302);
        $this->assertEquals('https://google.com', setting('maintenance_url'));
        $this->assertEquals('test', setting('maintenance_message'));
        $response->assertSessionHas('success', __('maintenance.settings.success'));
        $response = $this->performAdminAction('put', route('admin.settings.core.maintenance'), [
            'maintenance_enabled' => false,
            'maintenance_url' => 'https://google.com',
            'maintenance_message' => 'test',
            'maintenance_button_text' => 'test',
            'maintenance_button_icon' => 'test',
            'maintenance_button_url' => 'https://google.com',
        ]);
    }
}
