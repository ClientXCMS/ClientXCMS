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

namespace Tests\Unit\Rules;

use App\Rules\RendersWithTemplateGrammar;
use App\Services\Mail\LegacySyntaxScanner;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RendersWithTemplateGrammarTest extends TestCase
{
    private RendersWithTemplateGrammar $rule;

    protected function setUp(): void
    {
        parent::setUp();
        // French is the reference locale tracked with the code; pinning it keeps
        // the message assertion about the rule rather than about translations.
        app()->setLocale('fr');
        $this->rule = new RendersWithTemplateGrammar(new LegacySyntaxScanner);
    }

    private function failuresFor(mixed $value): array
    {
        $failures = [];
        $this->rule->validate('mail_greeting', $value, function (string $message) use (&$failures) {
            $failures[] = $message;
        });

        return $failures;
    }

    #[DataProvider('provideAcceptedContent')]
    public function test_accepts_content_the_renderer_understands(mixed $value): void
    {
        $this->assertSame([], $this->failuresFor($value));
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function provideAcceptedContent(): iterable
    {
        yield 'plain text' => ['Bonjour,'];
        yield 'a field' => ['Bonjour {{ customer.firstname }},'];
        yield 'a section' => ['{{#customer.firstname}}Bonjour{{/customer.firstname}}'];
        yield 'a recipient placeholder' => ['Bonjour %firstname%,'];
        yield 'empty' => [''];
        yield 'not a string' => [null];
    }

    #[DataProvider('provideRejectedContent')]
    public function test_rejects_content_that_would_be_sent_as_is(string $value): void
    {
        $this->assertNotSame([], $this->failuresFor($value), 'Should have been refused: '.$value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideRejectedContent(): iterable
    {
        yield 'old variable' => ['Bonjour {{ $customer->firstname }},'];
        yield 'old condition' => ['@if($ip)Bonjour@endif'];
        yield 'helper call' => ['{{ formatted_price($invoice->total, $invoice->currency) }}'];
        yield 'php tag' => ['<?php echo 1; ?>'];
        yield 'raw echo' => ['{!! $x !!}'];
    }

    public function test_names_the_construct_so_the_message_is_actionable(): void
    {
        $failures = $this->failuresFor('Bonjour {{ $customer->firstname }},');

        $this->assertStringContainsString('{{ $customer->firstname }}', $failures[0]);
    }
}
