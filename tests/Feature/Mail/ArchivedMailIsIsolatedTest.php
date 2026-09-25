<?php

/*
 * This file is part of the CLIENTXCMS project.
 * It is the property of the CLIENTXCMS association.
 *
 * Personal and non-commercial use of this source code is permitted.
 * However, any use in a project that generates profit (directly or indirectly),
 * or any reuse for commercial purposes, requires prior authorization from CLIENTXCMS.
 *
 * To request permission or for more information, please contact our support:
 * https://clientxcms.com/client/support
 *
 * Learn more about CLIENTXCMS License at:
 * https://clientxcms.com/eula
 *
 * Year: 2025
 */

namespace Tests\Feature\Mail;

use App\Models\Account\Customer;
use App\Models\Account\EmailMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A sent mail is archived as HTML and handed back to a browser. The body was
 * written by staff, so the browser must not run what the mail client never did.
 */
class ArchivedMailIsIsolatedTest extends TestCase
{
    use RefreshDatabase;

    private const HOSTILE_BODY = '<p>Bonjour</p><script>alert(1)</script><img src=x onerror=alert(1)>';

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customer = Customer::factory()->create();
        // The archive factory points at a template, and the table requires one.
        \App\Models\Admin\EmailTemplate::create([
            'name' => 'probe',
            'subject' => 'Sujet',
            'content' => 'Corps',
            'button_text' => null,
            'locale' => 'fr_FR',
        ]);
    }

    private function archivedMail(): EmailMessage
    {
        return EmailMessage::factory()->create([
            'recipient' => $this->customer->email,
            'recipient_id' => $this->customer->id,
            'subject' => 'Sujet',
            'content' => self::HOSTILE_BODY,
        ]);
    }

    public function test_the_customer_copy_forbids_every_script(): void
    {
        $response = $this->actingAs($this->customer, 'web')
            ->get(route('front.emails.show', $this->archivedMail()));

        $response->assertOk();
        $this->assertCspForbidsScripts($response->headers->get('Content-Security-Policy'));
    }

    public function test_the_staff_copy_forbids_every_script(): void
    {
        $response = $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.emails.show', $this->archivedMail()));

        $response->assertOk();
        $this->assertCspForbidsScripts($response->headers->get('Content-Security-Policy'));
    }

    /**
     * The body is served untouched on purpose: an archive proves what was sent.
     * The policy is what makes serving it safe, so both must hold together.
     */
    public function test_the_body_is_served_unchanged(): void
    {
        $response = $this->actingAs($this->customer, 'web')
            ->get(route('front.emails.show', $this->archivedMail()));

        $response->assertSee('<script>alert(1)</script>', false);
    }

    public function test_the_archive_cannot_be_framed(): void
    {
        $response = $this->actingAs($this->customer, 'web')
            ->get(route('front.emails.show', $this->archivedMail()));

        $this->assertSame('DENY', $response->headers->get('X-Frame-Options'));
        $this->assertStringContainsString("frame-ancestors 'none'", $response->headers->get('Content-Security-Policy'));
    }

    /**
     * Mail is written with inline styles and remote images; neither executes.
     */
    public function test_styles_and_images_still_work(): void
    {
        $response = $this->actingAs($this->customer, 'web')
            ->get(route('front.emails.show', $this->archivedMail()));

        $policy = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("style-src 'unsafe-inline'", $policy);
        $this->assertStringContainsString('img-src * data:', $policy);
    }

    private function assertCspForbidsScripts(?string $policy): void
    {
        $this->assertNotNull($policy, 'No Content-Security-Policy on a response carrying staff-written HTML.');
        $this->assertStringContainsString("default-src 'none'", $policy);
        $this->assertStringNotContainsString('script-src', $policy, 'A script-src would loosen what default-src already forbids.');
    }

    private function admin(): \App\Models\Admin\Admin
    {
        return \App\Models\Admin\Admin::factory()->create();
    }
}
