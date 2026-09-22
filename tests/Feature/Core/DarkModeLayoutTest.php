<?php

namespace Tests\Feature\Core;

use App\Models\Account\Customer;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DarkModeLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_front_layout_puts_dark_class_on_html_not_body(): void
    {
        $response = $this->withSession(['dark_mode' => true])->get(route('home'));

        $response->assertOk();
        $this->assertDarkClassOnHtmlOnly($response->getContent());
    }

    public function test_client_layout_puts_dark_class_on_html_not_body(): void
    {
        $customer = Customer::factory()->create(['dark_mode' => true]);

        $response = $this->actingAs($customer, 'web')->get(route('front.client.index'));

        $response->assertOk();
        $this->assertDarkClassOnHtmlOnly($response->getContent());
    }

    public function test_admin_layout_puts_dark_class_on_html_not_body(): void
    {
        $this->seed(AdminSeeder::class);
        $admin = \App\Models\Admin\Admin::firstOrFail();
        $admin->dark_mode = true;
        $admin->save();

        $response = $this->actingAs($admin, 'admin')->get(route('admin.dashboard'));

        $response->assertOk();
        $this->assertDarkClassOnHtmlOnly($response->getContent());
    }

    private function assertDarkClassOnHtmlOnly(string $html): void
    {
        preg_match('/<html\b[^>]*class="([^"]*)"/i', $html, $htmlMatch);
        preg_match('/<body\b[^>]*class="([^"]*)"/i', $html, $bodyMatch);

        $htmlClasses = explode(' ', trim($htmlMatch[1] ?? ''));
        $bodyClasses = explode(' ', trim($bodyMatch[1] ?? ''));

        $this->assertContains('dark', $htmlClasses, 'Expected <html> to carry the "dark" class, matching the JS toggle target.');
        $this->assertNotContains('dark', $bodyClasses, '<body> must not carry a bare "dark" class: the click toggle only flips <html>, so a body-side class desyncs and requires a page reload to reflect the real state.');
    }
}
