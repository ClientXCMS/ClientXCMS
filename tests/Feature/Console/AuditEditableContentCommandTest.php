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

namespace Tests\Feature\Console;

use App\Models\Admin\EmailTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditEditableContentCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        EmailTemplate::query()->delete();
    }

    private function template(string $name, string $content, string $subject = 'Subject'): void
    {
        EmailTemplate::create([
            'name' => $name,
            'subject' => $subject,
            'content' => $content,
            'button_text' => null,
            'locale' => 'fr_FR',
        ]);
    }

    public function test_reports_success_when_every_template_converts(): void
    {
        $this->template('welcome', 'Hello {{ $customer->firstname }}');

        $this->artisan('content:audit')
            ->expectsOutputToContain('Every stored template converts on its own.')
            ->assertSuccessful();
    }

    public function test_reports_failure_and_names_the_construct_needing_a_decision(): void
    {
        $this->template('invoice', '{{ formatted_price($invoice->total, $invoice->currency) }}');

        $this->artisan('content:audit')
            ->expectsOutputToContain('formatted_price')
            ->assertFailed();
    }

    public function test_counts_a_clean_template_instead_of_hiding_it(): void
    {
        $this->template('plain', 'Nothing to convert here.');

        $this->artisan('content:audit')
            ->expectsOutputToContain('nothing to do')
            ->assertSuccessful();
    }

    public function test_scans_the_subject_as_well_as_the_body(): void
    {
        $this->template('subject_only', 'Plain body.', '{{ formatted_price($invoice->total, $invoice->currency) }}');

        $this->artisan('content:audit')
            ->expectsOutputToContain('subject')
            ->assertFailed();
    }

    public function test_says_so_when_there_is_nothing_stored(): void
    {
        $this->artisan('content:audit')
            ->expectsOutputToContain('No mail template stored.')
            ->assertSuccessful();
    }
}
