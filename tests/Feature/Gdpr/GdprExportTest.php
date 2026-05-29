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
}
