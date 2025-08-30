<?php

namespace Tests\Feature\Admin\Settings;

use Tests\TestCase;

class SettingsSecurityControllerTest extends TestCase
{
    public function test_it_show_settings_security()
    {
        $response = $this->performAdminAction('get', route('admin.settings.show', ['card' => 'security', 'uuid' => 'security']));
        $response->assertStatus(200);
    }

    public function test_it_show_settings_security_without_permission()
    {
        $response = $this->performAdminAction('get', route('admin.settings.show', ['card' => 'security', 'uuid' => 'security']), [], ['admin.manage_settings']);
        $response->assertStatus(403);
    }

    public function test_it_save_settings_security()
    {
        $response = $this->performAdminAction('put', route('admin.settings.security'), [
            'hash_driver' => 'bcrypt',
            'allow_reset_password' => 'true',
            'allow_registration' => 'true',
            'auto_confirm_registration' => 'true',
            'force_login_client' => 'true',
            'password_timeout' => 60,
            'banned_emails' => 'test@clientxcms.com',
            'captcha_driver' => 'none',
            'admin_prefix' => 'admin',
        ]);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $this->assertEquals('bcrypt', setting('hash_driver'));
        $this->assertEquals('true', setting('allow_reset_password'));
        $this->assertEquals('true', setting('allow_registration'));
        $this->assertEquals('true', setting('auto_confirm_registration'));
        $this->assertEquals('true', setting('force_login_client'));
        $this->assertEquals(60, setting('password_timeout'));
        $this->assertEquals('test@clientxcms.com', setting('banned_emails'));
        $this->assertEquals('none', setting('captcha_driver'));
    }

    public function test_it_update_settings_security_with_captcha_driver()
    {
        $response = $this->performAdminAction('put', route('admin.settings.security'), [
            'hash_driver' => 'bcrypt',
            'allow_reset_password' => 'true',
            'allow_registration' => 'true',
            'auto_confirm_registration' => 'true',
            'force_login_client' => 'true',
            'password_timeout' => 60,
            'banned_emails' => 'test@clientxcms.com',
            'captcha_driver' => 'recaptcha',
            'admin_prefix' => 'admin',
            'captcha_site_key' => 'test_site_key',
            'captcha_secret_key' => 'test_secret_key',
        ]);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $this->assertEquals('recaptcha', setting('captcha_driver'));
        $this->assertEquals('test_site_key', setting('captcha_site_key'));
        $this->assertEquals('test_secret_key', setting('captcha_secret_key'));
    }
}
