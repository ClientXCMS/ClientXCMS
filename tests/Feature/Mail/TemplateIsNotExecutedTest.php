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
use App\Models\Provisioning\Server;
use App\Models\Provisioning\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The security contract of this change, exercised through the real entry point:
 * a template is data, and what a notification hands over is prepared first.
 */
class TemplateIsNotExecutedTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        EmailTemplate::query()->delete();
        $this->customer = Customer::factory()->create(['locale' => 'fr_FR']);
    }

    private function template(string $content, string $subject = 'Sujet'): void
    {
        EmailTemplate::create([
            'name' => 'probe',
            'subject' => $subject,
            'content' => $content,
            'button_text' => null,
            'locale' => 'fr_FR',
        ]);
    }

    private function body(array $context = []): string
    {
        $mail = EmailTemplate::getMailMessage('probe', '', $context, $this->customer, 'fr_FR');

        return collect($mail->introLines)->map(fn ($line) => (string) $line)->implode("\n");
    }

    #[DataProvider('provideThingsBladeWouldHaveRun')]
    public function test_a_template_is_never_executed(string $content): void
    {
        $this->template($content);

        $this->assertStringContainsString($content, $this->body());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideThingsBladeWouldHaveRun(): iterable
    {
        yield 'php tag' => ['<?php echo 1; ?>'];
        yield 'blade directive' => ['@php echo 1; @endphp'];
        yield 'raw echo' => ['{!! 1 + 1 !!}'];
        yield 'function call' => ['{{ strtoupper(chr(97)) }}'];
        yield 'config read' => ["{{ config('app.key') }}"];
        yield 'setting read' => ["{{ setting('mail_smtp_password') }}"];
        yield 'array callable' => ['{{ [Foo::class, chr(98)]() }}'];
    }

    public function test_the_subject_is_never_executed(): void
    {
        $this->template('Corps', '{{ strtoupper(chr(97)) }}');

        $mail = EmailTemplate::getMailMessage('probe', '', [], $this->customer, 'fr_FR');

        $this->assertStringContainsString('{{ strtoupper(chr(97)) }}', $mail->subject);
        $this->assertStringNotContainsString('A', str_replace('{{ strtoupper(chr(97)) }}', '', $mail->subject));
    }

    public function test_a_template_cannot_reach_the_server_credentials_through_a_service(): void
    {
        $server = Server::factory()->create(['username' => 'root-secret', 'password' => 'pass-secret']);
        $service = Service::factory()->create(['server_id' => $server->id, 'customer_id' => $this->customer->id]);
        $this->template('{{ service.server.password }}|{{ service.server_name }}');

        $body = $this->body(['service' => $service]);

        $this->assertStringNotContainsString('pass-secret', $body);
        $this->assertStringNotContainsString('root-secret', $body);
    }

    public function test_a_prepared_view_still_reaches_the_template(): void
    {
        $invoice = Invoice::factory()->create(['total' => 49.99, 'currency' => 'EUR']);
        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'name' => 'Hébergement Web',
            'unit_price_ht' => 20.0,
            'unit_setup_ht' => 0.0,
            'quantity' => 2,
        ]);
        $this->template('Total {{ invoice.total }} {{#invoice.items}}[{{ name }}]{{/invoice.items}}');

        $body = $this->body(['invoice' => $invoice->fresh()]);

        $this->assertStringContainsString('€', $body);
        $this->assertStringContainsString('[Hébergement Web]', $body);
    }

    public function test_a_scalar_passed_by_a_notification_still_works(): void
    {
        $this->template('Il reste {{ days }} jours.');

        $this->assertStringContainsString('Il reste 7 jours.', $this->body(['days' => 7]));
    }

    /**
     * An object with no prepared view is dropped rather than handed over, and the
     * mail still goes out with the rest of its content.
     */
    public function test_an_unpreparable_object_does_not_break_the_mail(): void
    {
        $this->template('Avant {{ mystery }} après.');

        $body = $this->body(['mystery' => new \DateTimeImmutable('2026-01-01')]);

        $this->assertStringContainsString('Avant', $body);
        $this->assertStringContainsString('après.', $body);
    }
}
