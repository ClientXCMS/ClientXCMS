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
use App\Models\Admin\EmailTemplate;
use App\Models\Billing\Invoice;
use App\Models\Billing\InvoiceItem;
use App\Services\Mail\StoredTemplateMigrator;
use App\Services\Mail\TemplateRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateMigrationTest extends TestCase
{
    use RefreshDatabase;

    /** The invoice template exactly as this project has shipped it. */
    private const SHIPPED_INVOICE_TEMPLATE = <<<'BLADE'
    <strong>Total</strong>: {{ formatted_price($invoice->total, $invoice->currency) }} <br/>
    @foreach($invoice->items as $item)
    <strong>Nom</strong> : {{ $item->name }} <br/>
    <strong>Prix </strong> : {{ formatted_price($item->price(), $invoice->currency) }} <br/>
    @endforeach
    BLADE;

    protected function setUp(): void
    {
        parent::setUp();
        EmailTemplate::query()->delete();
        Customer::factory()->create();
    }

    private function storeTemplate(string $content): EmailTemplate
    {
        return EmailTemplate::create([
            'name' => 'invoice_created',
            'subject' => 'Votre facture',
            'content' => $content,
            'button_text' => null,
            'locale' => 'fr_FR',
        ]);
    }

    private function migrator(): StoredTemplateMigrator
    {
        return app(StoredTemplateMigrator::class);
    }

    public function test_the_shipped_invoice_template_ends_up_fully_in_the_new_grammar(): void
    {
        $template = $this->storeTemplate(self::SHIPPED_INVOICE_TEMPLATE);

        $this->migrator()->migrate();

        $content = $template->fresh()->content;
        $this->assertStringNotContainsString('@foreach', $content);
        $this->assertStringNotContainsString('formatted_price', $content);
        $this->assertStringNotContainsString('$', $content);
        $this->assertStringContainsString('{{#invoice.items}}', $content);
    }

    /**
     * The point of the whole chain: a migrated template, rendered with what the
     * prepared view hands over, produces the values the original produced.
     */
    public function test_a_migrated_template_renders_the_real_values(): void
    {
        $template = $this->storeTemplate(self::SHIPPED_INVOICE_TEMPLATE);
        $this->migrator()->migrate();

        $invoice = Invoice::factory()->create(['total' => 49.99, 'currency' => 'EUR']);
        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'name' => 'Hébergement Web',
            'unit_price_ht' => 20.0,
            'unit_setup_ht' => 0.0,
            'quantity' => 2,
        ]);

        $rendered = (new TemplateRenderer)->render(
            $template->fresh()->content,
            ['invoice' => $invoice->fresh()->toMailData('fr_FR')],
        );

        $this->assertStringContainsString('Hébergement Web', $rendered);
        $this->assertStringContainsString('€', $rendered, 'The formatted total should have been inserted.');
        $this->assertDoesNotMatchRegularExpression('/\{\{.*\}\}/', $rendered, 'A placeholder was left unresolved.');
    }

    public function test_running_the_migration_again_changes_nothing(): void
    {
        $this->storeTemplate(self::SHIPPED_INVOICE_TEMPLATE);

        $first = $this->migrator()->migrate();
        $second = $this->migrator()->migrate();

        $this->assertGreaterThan(0, $first['changed']);
        $this->assertSame(0, $second['changed']);
    }

    public function test_a_dry_run_writes_nothing(): void
    {
        $template = $this->storeTemplate(self::SHIPPED_INVOICE_TEMPLATE);

        $result = $this->migrator()->migrate(apply: false);

        $this->assertGreaterThan(0, $result['changed']);
        $this->assertSame(self::SHIPPED_INVOICE_TEMPLATE, $template->fresh()->content);
    }

    public function test_what_it_cannot_translate_is_reported_and_left_alone(): void
    {
        $before = '@if($reward_amount > 0)Bravo@endif';
        $template = $this->storeTemplate($before);

        $result = $this->migrator()->migrate();

        $this->assertSame($before, $template->fresh()->content);
        $this->assertNotEmpty($result['pending']);
    }

    public function test_the_command_reports_what_is_left(): void
    {
        $this->storeTemplate('@if($reward_amount > 0)Bravo@endif');

        $this->artisan('content:migrate')
            ->expectsOutputToContain('reward_amount')
            ->assertFailed();
    }

    public function test_the_command_succeeds_when_everything_converts(): void
    {
        $this->storeTemplate(self::SHIPPED_INVOICE_TEMPLATE);

        $this->artisan('content:migrate')
            ->expectsOutputToContain('Nothing left for a human to decide.')
            ->assertSuccessful();
    }
}
