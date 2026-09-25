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

namespace Tests\Unit\Models;

use App\Contracts\Notifications\ProvidesMailData;
use App\Models\Account\Customer;
use App\Models\Billing\Invoice;
use App\Models\Billing\InvoiceItem;
use App\Models\Helpdesk\SupportTicket;
use App\Models\Provisioning\Server;
use App\Models\Provisioning\Service;
use App\Services\Mail\TemplateRenderer;
use Database\Factories\Helpdesk\DepartmentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailDataTest extends TestCase
{
    use RefreshDatabase;

    /** Both the invoice and the ticket factories look up an existing customer. */
    protected function setUp(): void
    {
        parent::setUp();
        Customer::factory()->create();
    }

    private function invoice(): Invoice
    {
        $invoice = Invoice::factory()->create(['total' => 49.99, 'currency' => 'EUR']);
        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'name' => 'Hébergement Web',
            'unit_price_ht' => 20.0,
            'unit_setup_ht' => 0.0,
            'quantity' => 2,
        ]);

        return $invoice->fresh();
    }

    /**
     * Asserts the value carries its currency rather than a specific separator:
     * the exact shape depends on the ICU data installed, which is an environment
     * concern, whereas "formatted rather than raw" is what this contract owes.
     */
    public function test_an_invoice_hands_over_a_formatted_total(): void
    {
        $data = $this->invoice()->toMailData('fr_FR');

        $this->assertStringContainsString('€', $data['total']);
        $this->assertNotSame('49.99', $data['total'], 'The raw value leaked instead of the formatted one.');
    }

    public function test_an_invoice_hands_over_its_lines_as_a_list(): void
    {
        $data = $this->invoice()->toMailData('fr_FR');

        $this->assertCount(1, $data['items']);
        $this->assertSame('Hébergement Web', $data['items'][0]['name']);
        $this->assertStringContainsString('40', $data['items'][0]['price'], 'Line price is quantity times unit price.');
        $this->assertStringContainsString('€', $data['items'][0]['price']);
    }

    public function test_a_ticket_hands_over_its_department_in_the_template_locale(): void
    {
        $department = DepartmentFactory::new()->create(['name' => 'Facturation']);
        $ticket = SupportTicket::factory()->create(['department_id' => $department->id, 'subject' => 'Ma facture']);

        $data = $ticket->toMailData('fr_FR');

        $this->assertSame('Ma facture', $data['subject']);
        $this->assertSame('Facturation', $data['department']);
    }

    /**
     * The contract in one assertion: whatever a model hands over must be
     * something the renderer accepts, which means no object anywhere in it.
     */
    public function test_every_model_hands_over_something_the_renderer_accepts(): void
    {
        $models = [
            'customer' => Customer::factory()->create(),
            'server' => Server::factory()->create(),
            'service' => Service::factory()->create(),
            'invoice' => $this->invoice(),
            'ticket' => SupportTicket::factory()->create(),
        ];

        $renderer = new TemplateRenderer;
        foreach ($models as $name => $model) {
            $this->assertInstanceOf(ProvidesMailData::class, $model);
            $renderer->render('{{ anything }}', [$name => $model->toMailData('fr_FR')]);
        }

        $this->addToAssertionCount(count($models));
    }

    /**
     * Credentials were reachable from a template through the server relation.
     * The prepared view is the layer that makes that impossible.
     */
    public function test_a_server_hands_over_no_credential(): void
    {
        $server = Server::factory()->create(['username' => 'root-secret', 'password' => 'pass-secret']);

        $data = $server->toMailData('fr_FR');

        $this->assertNotContains('root-secret', $data);
        $this->assertNotContains('pass-secret', $data);
        $this->assertArrayNotHasKey('username', $data);
        $this->assertArrayNotHasKey('password', $data);
    }
}
