<?php

namespace Tests\Feature\Account;

use App\Models\Account\Customer;
use App\Models\Account\CustomerNote;
use App\Models\Store\Basket\Basket;
use App\Services\Account\GdprExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class GdprExportCompletenessTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customer = Customer::factory()->create();
    }

    protected function tearDown(): void
    {
        $dir = storage_path('app/'.GdprExportService::STORAGE_DIR.'/'.$this->customer->id);
        foreach (glob($dir.'/*.zip') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($dir);

        parent::tearDown();
    }

    public function test_the_trusted_devices_are_exported(): void
    {
        $this->customer->trustTwoFactorIp('203.0.113.9', 'Mozilla/5.0 Firefox');

        $metadata = $this->entry('metadata.json');

        $this->assertStringContainsString('203.0.113.9', $metadata, 'A trusted device is an ip and a browser kept on file: that is data about the person');
        $this->assertStringContainsString('Firefox', $metadata);
    }

    public function test_the_authentication_secrets_are_never_exported(): void
    {
        $this->customer->twoFactorEnable('JBSWY3DPEHPK3PXP');
        $this->customer->attachMetadata('some_extension_api_key', 'extension-secret-value');

        $metadata = $this->entry('metadata.json');

        $this->assertStringNotContainsString('JBSWY3DPEHPK3PXP', $metadata, 'Handing the second factor back in an archive defeats the second factor');
        $this->assertStringNotContainsString('2fa_recovery_codes', $metadata);
        $this->assertStringNotContainsString('extension-secret-value', $metadata, 'A key an extension called a secret must not ride along either');
    }

    public function test_the_passkeys_are_exported_without_their_credential(): void
    {
        $this->customer->passkeys()->create([
            'name' => 'Yubikey',
            'credential_id' => 'credential-id-under-test',
            'credential' => ['publicKey' => 'not-for-the-archive'],
        ]);

        $passkeys = $this->entry('passkeys.json');

        $this->assertStringContainsString('Yubikey', $passkeys, 'API tokens were listed, passkeys were not, although both are ways in');
        $this->assertStringNotContainsString('not-for-the-archive', $passkeys);
    }

    public function test_the_baskets_are_exported(): void
    {
        Basket::create([
            'uuid' => 'basket-under-test',
            'user_id' => (string) $this->customer->id,
            'ip_address' => '203.0.113.4',
        ]);

        $baskets = $this->entry('baskets.json');

        $this->assertStringContainsString('basket-under-test', $baskets);
        $this->assertStringContainsString('203.0.113.4', $baskets, 'A basket keeps the address the order was started from');
    }

    public function test_the_staff_notes_are_exported_without_their_author(): void
    {
        $this->seed(\Database\Seeders\AdminSeeder::class);
        CustomerNote::create([
            'customer_id' => $this->customer->id,
            'author_id' => \App\Models\Admin\Admin::first()->id,
            'content' => 'Called about the invoice, sounded annoyed.',
        ]);

        $notes = $this->entry('staff_notes.json');

        $this->assertStringContainsString('sounded annoyed', $notes, 'What the staff writes about somebody is data about that somebody');
        $this->assertStringNotContainsString('author_id', $notes, 'The agent who wrote it is a third party');
    }

    public function test_the_manifest_lists_the_new_files(): void
    {
        $manifest = json_decode($this->entry('manifest.json'), true);

        $this->assertSame(3, $manifest['export_version']);
        foreach (['metadata.json', 'passkeys.json', 'baskets.json', 'staff_notes.json'] as $file) {
            $this->assertContains($file, $manifest['files']);
        }
    }

    private function entry(string $name): string
    {
        $path = app(GdprExportService::class)->buildArchive($this->customer->fresh());

        $zip = new ZipArchive;
        $this->assertTrue($zip->open(storage_path('app/'.$path)) === true, 'The archive must open');
        $content = $zip->getFromName($name);
        $zip->close();

        $this->assertNotFalse($content, sprintf('%s is missing from the archive', $name));

        return $content;
    }
}
