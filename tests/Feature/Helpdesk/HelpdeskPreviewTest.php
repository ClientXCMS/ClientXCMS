<?php

namespace Tests\Feature\Helpdesk;

use App\Models\Account\Customer;
use App\Models\Admin\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * v2.16 — covers the shared markdown preview endpoint that powers the
 * "Preview" button in the helpdesk editor.
 *
 * The endpoint must:
 *   * require an authenticated user (any guard: web OR admin)
 *   * return the same HTML Parsedown produces (safe mode on)
 *   * reject overly long payloads
 *   * never persist anything
 */
class HelpdeskPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_anonymous_caller_is_rejected(): void
    {
        $response = $this->postJson('/helpdesk/preview', ['content' => 'hi']);

        // 401 from the auth middleware (status varies on auth driver — be lenient)
        $this->assertContains($response->status(), [401, 403, 302], 'auth must reject the call');
    }

    public function test_signed_in_customer_gets_html_back(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->actingAs($customer, 'web')->postJson('/helpdesk/preview', [
            'content' => "# Hello\n\n- one\n- two\n\n---\n\nclosing.",
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['html']);
        $html = $response->json('html');
        $this->assertStringContainsString('<h1>Hello</h1>', $html);
        $this->assertStringContainsString('<ul>', $html);
        $this->assertStringContainsString('<hr', $html);
    }

    public function test_signed_in_admin_can_preview(): void
    {
        $admin = Admin::factory()->create();

        $response = $this->actingAs($admin, 'admin')->postJson('/helpdesk/preview', [
            'content' => '**Bold**',
        ]);

        $response->assertOk();
        $this->assertSame('<p><strong>Bold</strong></p>', trim($response->json('html')));
    }

    public function test_unsafe_html_is_neutralised(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->actingAs($customer, 'web')->postJson('/helpdesk/preview', [
            'content' => '<script>alert(1)</script>',
        ]);

        $response->assertOk();
        // Parsedown safe mode keeps the literal text and escapes the tags.
        $this->assertStringNotContainsString('<script', $response->json('html'));
    }

    public function test_oversized_payload_is_rejected(): void
    {
        $customer = Customer::factory()->create();
        $huge = str_repeat('A', 20001);

        $response = $this->actingAs($customer, 'web')->postJson('/helpdesk/preview', [
            'content' => $huge,
        ]);

        $response->assertStatus(422);
    }
}
