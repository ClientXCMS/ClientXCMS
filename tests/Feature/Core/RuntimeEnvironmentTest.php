<?php

namespace Tests\Feature\Core;

use App\Core\Environment\RuntimeEnvironment;
use App\Models\ActionLog;
use App\Models\Admin\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RuntimeEnvironmentTest extends TestCase
{
    use RefreshDatabase;

    private ?string $tempDir = null;

    protected function tearDown(): void
    {
        // parent::tearDown() must run even if cleanup fails, or the RefreshDatabase transaction stays open.
        try {
            if ($this->tempDir !== null) {
                foreach (array_diff(scandir($this->tempDir) ?: [], ['.', '..']) as $file) {
                    @chmod($this->tempDir.'/'.$file, 0644);
                    @unlink($this->tempDir.'/'.$file);
                }
                @rmdir($this->tempDir);
            }
        } finally {
            parent::tearDown();
        }
    }

    public function test_managed_value_is_stored_in_settings_and_read_back(): void
    {
        RuntimeEnvironment::put(['STRIPE_PUBLIC_KEY' => 'pk_test_123']);

        $this->assertSame('pk_test_123', runtime_env('STRIPE_PUBLIC_KEY'));
        $this->assertDatabaseHas('settings', ['name' => 'env_stripe_public_key', 'value' => 'pk_test_123']);
    }

    public function test_secret_value_is_encrypted_at_rest(): void
    {
        RuntimeEnvironment::put(['STRIPE_PRIVATE_KEY' => 'sk_test_secret']);

        $raw = DB::table('settings')->where('name', 'env_stripe_private_key')->value('value');
        $this->assertNotSame('sk_test_secret', $raw);
        $this->assertSame('sk_test_secret', decrypt($raw, false));
        $this->assertSame('sk_test_secret', runtime_env('STRIPE_PRIVATE_KEY'));
    }

    public function test_falls_back_to_env_when_nothing_is_stored(): void
    {
        $this->setProcessEnv('SUMUP_MERCHANT_CODE', 'FROM_ENV');

        $this->assertSame('FROM_ENV', runtime_env('SUMUP_MERCHANT_CODE'));
    }

    public function test_cleared_value_does_not_fall_back_to_stale_env(): void
    {
        $this->setProcessEnv('STORE_VAT_COUNTRY', 'BE');

        RuntimeEnvironment::put(['STORE_VAT_COUNTRY' => null]);

        $this->assertSame('', runtime_env('STORE_VAT_COUNTRY', 'FR'));
    }

    public function test_action_log_masks_secret_values(): void
    {
        RuntimeEnvironment::put(['MOLLIE_KEY' => 'live_secret', 'MOLLIE_TEST_MODE' => 'false']);

        $log = ActionLog::where('action', ActionLog::SETTINGS_UPDATED)->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertNotEmpty($log->entries);
        $this->assertStringNotContainsString('live_secret', json_encode($log->getAttributes()).$log->entries->toJson());
    }

    public function test_stored_values_override_config(): void
    {
        RuntimeEnvironment::put(['MAIL_HOST' => 'smtp.example.test', 'HASH_DRIVER' => 'argon']);

        RuntimeEnvironment::applyToConfig();

        $this->assertSame('smtp.example.test', config('mail.mailers.smtp.host'));
        $this->assertSame('argon', config('hashing.driver'));
    }

    public function test_flag_parses_strings_and_booleans(): void
    {
        RuntimeEnvironment::put(['MOLLIE_TEST_MODE' => 'false', 'PAYPAL_SANDBOX' => true]);

        $this->assertFalse(RuntimeEnvironment::flag('MOLLIE_TEST_MODE', true));
        $this->assertTrue(RuntimeEnvironment::flag('PAYPAL_SANDBOX'));
    }

    public function test_import_copies_env_file_values_without_overwriting(): void
    {
        Setting::create(['name' => 'env_stripe_public_key', 'value' => 'already_stored']);
        $path = $this->writeEnvFile("STRIPE_PUBLIC_KEY=from_file\nMOLLIE_KEY=\"mollie secret\"\nSQUARE_LOCATION_ID=\nAPP_KEY=base64:ignored\n");

        $imported = RuntimeEnvironment::importFromEnvFile($path);

        $this->assertSame(['MOLLIE_KEY'], $imported);
        $this->assertSame('mollie secret', Setting::where('name', 'env_mollie_key')->first()->value);
        $this->assertSame('already_stored', Setting::where('name', 'env_stripe_public_key')->first()->value);
        $this->assertDatabaseMissing('settings', ['name' => 'env_square_location_id']);
        $this->assertDatabaseMissing('settings', ['name' => 'env_app_key']);
    }

    public function test_bootstrap_values_are_returned_when_env_is_read_only(): void
    {
        $path = $this->writeEnvFile("APP_NAME=Old\n");
        chmod($path, 0444);
        $previousPath = $this->app->environmentPath();
        $previousFile = $this->app->environmentFile();
        $this->app->useEnvironmentPath(dirname($path))->loadEnvironmentFrom(basename($path));

        try {
            $pending = RuntimeEnvironment::put(['APP_NAME' => 'New <b>name</b>', 'STRIPE_PUBLIC_KEY' => 'pk']);
        } finally {
            $this->app->useEnvironmentPath($previousPath)->loadEnvironmentFrom($previousFile);
        }

        $this->assertSame(['APP_NAME' => 'New <b>name</b>'], $pending);
        $this->assertStringContainsString('APP_NAME=Old', file_get_contents($path));
        $this->assertSame('pk', runtime_env('STRIPE_PUBLIC_KEY'));
        $this->assertStringNotContainsString('<b>', RuntimeEnvironment::pendingWarning($pending));
        $this->assertNull(RuntimeEnvironment::pendingWarning([]));
    }

    public function test_mail_settings_are_saved_without_touching_env(): void
    {
        $response = $this->performAdminAction('put', route('admin.settings.core.email'), [
            'mail_from_address' => 'test@test.com',
            'mail_from_name' => 'test',
            'mail_greeting' => 'test',
            'mail_salutation' => 'test',
            'mail_domain' => config('app.url'),
            'mail_smtp_enable' => true,
            'mail_smtp_host' => 'example.com',
            'mail_smtp_port' => 465,
            'mail_smtp_username' => 'user',
            'mail_smtp_password' => 'secret',
            'mail_smtp_encryption' => 'ssl',
        ]);

        $response->assertSessionHas('success');
        $this->assertSame('example.com', runtime_env('MAIL_HOST'));
        $this->assertSame('secret', runtime_env('MAIL_PASSWORD'));
        $this->assertNotSame('secret', DB::table('settings')->where('name', 'env_mail_password')->value('value'));
    }

    private function setProcessEnv(string $key, string $value): void
    {
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        $this->beforeApplicationDestroyed(function () use ($key) {
            putenv($key);
            unset($_ENV[$key], $_SERVER[$key]);
        });
    }

    private function writeEnvFile(string $content): string
    {
        $this->tempDir ??= sys_get_temp_dir().'/ctx-runtime-env-'.bin2hex(random_bytes(4));
        if (! is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0700, true);
        }
        $path = $this->tempDir.'/.env';
        file_put_contents($path, $content);

        return $path;
    }
}
