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

namespace Tests\Unit\Services\Mail;

use App\Models\Account\Customer;
use App\Models\Provisioning\Server;
use App\Services\Mail\KnownMailVariables;
use Tests\TestCase;

class KnownMailVariablesTest extends TestCase
{
    private KnownMailVariables $variables;

    protected function setUp(): void
    {
        parent::setUp();
        $this->variables = new KnownMailVariables;
    }

    public function test_gathers_the_variables_every_source_declares(): void
    {
        $all = $this->variables->all();

        $this->assertContains('%customer_email%', $all);
        $this->assertContains('%service_name%', $all);
        $this->assertContains('%server_name%', $all);
        $this->assertContains('%firstname%', $all);
    }

    /**
     * The list is read from the models, so a variable dropped there disappears
     * here without anyone editing this class.
     */
    public function test_follows_the_models_rather_than_keeping_a_copy(): void
    {
        $this->assertSame(
            array_values(array_intersect($this->variables->all(), Server::getNotificationContextVariables())),
            array_values(Server::getNotificationContextVariables()),
        );
    }

    public function test_finds_nothing_unknown_in_a_content_using_declared_variables(): void
    {
        $this->assertSame([], $this->variables->unknownIn('Bonjour %firstname%, votre service %service_name% expire.'));
    }

    public function test_reports_a_variable_nothing_will_ever_fill(): void
    {
        $unknown = $this->variables->unknownIn('Votre mot de passe : %server_root_password%');

        $this->assertSame(['%server_root_password%'], $unknown);
    }

    public function test_reports_a_typo_in_a_variable_name(): void
    {
        $this->assertSame(['%custommer_email%'], $this->variables->unknownIn('Hello %custommer_email%'));
    }

    public function test_reports_each_unknown_variable_once(): void
    {
        $this->assertSame(['%nope%'], $this->variables->unknownIn('%nope% %nope% %nope%'));
    }

    public function test_ignores_a_lone_percent_sign(): void
    {
        $this->assertSame([], $this->variables->unknownIn('Remise de 20% sur le premier mois.'));
    }

    /**
     * Guards the recipient placeholders against drifting away from what
     * EmailTemplate::replacePlaceholders actually substitutes.
     */
    public function test_recipient_placeholders_are_the_ones_actually_substituted(): void
    {
        $customer = new Customer(['firstname' => 'Ada', 'lastname' => 'Lovelace', 'email' => 'ada@example.com']);

        $rendered = \App\Models\Admin\EmailTemplate::replacePlaceholders('%firstname%|%lastname%|%email%|%fullname%', $customer);

        $this->assertStringNotContainsString('%', $rendered, 'A recipient placeholder listed here is no longer substituted.');
    }
}
