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

    public function test_admin_dark_mode_button_icon_reflects_actual_admin_state(): void
    {
        $this->seed(AdminSeeder::class);
        $admin = \App\Models\Admin\Admin::firstOrFail();
        $admin->dark_mode = true;
        $admin->save();

        $response = $this->actingAs($admin, 'admin')->get(route('admin.dashboard'));

        $response->assertOk();
        $this->assertSunIconVisible($response->getContent());
    }

    public function test_admin_light_mode_button_icon_reflects_actual_admin_state(): void
    {
        $this->seed(AdminSeeder::class);
        $admin = \App\Models\Admin\Admin::firstOrFail();
        $admin->dark_mode = false;
        $admin->save();

        $response = $this->actingAs($admin, 'admin')->get(route('admin.dashboard'));

        $response->assertOk();
        $this->assertMoonIconVisible($response->getContent());
    }

    private function assertSunIconVisible(string $html): void
    {
        [$sunClasses, $moonClasses] = $this->extractIconClasses($html);

        $this->assertStringNotContainsString('hidden', $sunClasses, 'In dark mode, the sun icon (switch to light) must be visible.');
        $this->assertStringContainsString('hidden', $moonClasses, 'In dark mode, the moon icon must stay hidden.');
    }

    private function assertMoonIconVisible(string $html): void
    {
        [$sunClasses, $moonClasses] = $this->extractIconClasses($html);

        $this->assertStringContainsString('hidden', $sunClasses, 'In light mode, the sun icon must stay hidden.');
        $this->assertStringNotContainsString('hidden', $moonClasses, 'In light mode, the moon icon (switch to dark) must be visible.');
    }

    private function extractIconClasses(string $html): array
    {
        preg_match('/class="([^"]*)"\s+id="dark-mode-sun"/i', $html, $sunMatch);
        preg_match('/class="([^"]*)"\s+id="dark-mode-moon"/i', $html, $moonMatch);

        return [$sunMatch[1] ?? '', $moonMatch[1] ?? ''];
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
