<?php

namespace Tests\Feature\Gdpr;

use App\Models\Account\Customer;
use App\Models\Billing\Invoice;
use App\Services\Account\GdprExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class GdprExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_in_customer_can_request_export(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->actingAs($customer, 'web')->post(route('front.profile.export'));

        $response->assertRedirect(route('front.profile.index'));
        $response->assertSessionHas('success');
        $response->assertSessionHas('gdpr_export_url');
    }

    public function test_export_archive_contains_expected_files(): void
    {
        $customer = Customer::factory()->create();

        $service = new GdprExportService;
        $relative = $service->buildArchive($customer);

        $absolute = storage_path('app/' . $relative);
        $this->assertFileExists($absolute);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($absolute) === true);

        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $names[] = $zip->getNameIndex($i);
        }
        $zip->close();

        $this->assertContains('manifest.json', $names);
        $this->assertContains('profile.json', $names);
        $this->assertContains('invoices.json', $names);
        $this->assertContains('services.json', $names);
        $this->assertContains('tickets.json', $names);
        $this->assertContains('api_tokens.json', $names);
    }

    public function test_download_route_refuses_paths_outside_owner_prefix(): void
    {
        $customer = Customer::factory()->create();
        $intruder = Customer::factory()->create();

        // Build an archive for `intruder` then try to download it as `customer`
        $service = new GdprExportService;
        $relative = $service->buildArchive($intruder);

        // The route requires `signed`, so we sign the URL ourselves — this
        // simulates someone reusing an old signed URL after switching account.
        $url = \URL::temporarySignedRoute(
            'front.profile.export.download',
            now()->addDay(),
            ['path' => $relative]
        );

        $response = $this->actingAs($customer, 'web')->get($url);
        $response->assertForbidden();
    }

    public function test_build_archive_purges_zips_older_than_24h(): void
    {
        $customer = Customer::factory()->create();
        $service = new GdprExportService;

        $dir = storage_path('app/'.GdprExportService::STORAGE_DIR.'/'.$customer->id);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $stale = $dir.'/export-stale.zip';
        $fresh = $dir.'/export-fresh.zip';
        file_put_contents($stale, 'old');
        file_put_contents($fresh, 'new');
        touch($stale, now()->subDays(2)->getTimestamp());
        touch($fresh, now()->subHours(2)->getTimestamp());

        $service->buildArchive($customer);

        $this->assertFileDoesNotExist($stale);
        $this->assertFileExists($fresh);
    }

    public function test_invoice_number_with_path_traversal_chars_is_sanitised(): void
    {
        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'invoice_number' => 'CTX/../escape',
        ]);

        $service = new GdprExportService;
        $relative = $service->buildArchive($customer);

        $zip = new ZipArchive;
        $zip->open(storage_path('app/'.$relative));
        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entries[] = $zip->getNameIndex($i);
        }
        $zip->close();

        foreach ($entries as $name) {
            $this->assertStringNotContainsString('..', $name, "ZIP entry '$name' leaked the traversal sequence");
        }
    }
}
