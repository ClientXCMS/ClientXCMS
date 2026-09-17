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

use App\Services\Mail\LegacySyntaxScanner;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LegacySyntaxScannerTest extends TestCase
{
    private LegacySyntaxScanner $scanner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scanner = new LegacySyntaxScanner;
    }

    public function test_reports_plain_html_as_clean(): void
    {
        $report = $this->scanner->scan('<strong>Total</strong><br/>');

        $this->assertTrue($report->isClean());
        $this->assertSame('clean', $report->status());
    }

    public function test_reports_the_closed_grammar_as_clean(): void
    {
        $report = $this->scanner->scan('{{ invoice.total }}{{#invoice.items}}{{ name }}{{/invoice.items}}');

        $this->assertTrue($report->isClean());
    }

    #[DataProvider('provideConvertibleConstructs')]
    public function test_marks_a_construct_as_convertible(string $content, string $expected): void
    {
        $report = $this->scanner->scan($content);

        $this->assertContains($expected, $report->convertible);
        $this->assertSame([], $report->manual);
        $this->assertSame('convertible', $report->status());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideConvertibleConstructs(): iterable
    {
        yield 'bare variable' => ['Hello {{ $reason }}', '{{ $reason }}'];
        yield 'property path' => ['{{ $ticket->customer->fullName }}', '{{ $ticket->customer->fullName }}'];
        yield 'path without spaces' => ['{{ $invoice->id}}', '{{ $invoice->id}}'];
        yield 'condition on a path' => ['@if($ip) x @endif', '@if($ip)'];
        yield 'loop over a path' => ['@foreach($invoice->items as $item) x @endforeach', '@foreach($invoice->items as $item)'];
    }

    #[DataProvider('provideConstructsNeedingADecision')]
    public function test_marks_a_construct_as_manual(string $content, string $expected): void
    {
        $report = $this->scanner->scan($content);

        $this->assertContains($expected, $report->manual);
        $this->assertTrue($report->needsManualWork());
        $this->assertSame('manual', $report->status());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideConstructsNeedingADecision(): iterable
    {
        yield 'helper call' => ['{{ formatted_price($invoice->total, $invoice->currency) }}', '{{ formatted_price($invoice->total, $invoice->currency) }}'];
        yield 'method call' => ['{{ $item->price() }}', '{{ $item->price() }}'];
        yield 'date formatting' => ["{{ \$quote->due_date->format('d/m/Y') }}", "{{ \$quote->due_date->format('d/m/Y') }}"];
        yield 'comparison' => ['@if($reward_amount > 0) x @endif', '@if($reward_amount > 0)'];
        yield 'boolean expression' => ['@if($reward_given && $reward_amount > 0) x @endif', '@if($reward_given && $reward_amount > 0)'];
        yield 'negated expression' => ['@if(!$reward_given && $reward_amount > 0) x @endif', '@if(!$reward_given && $reward_amount > 0)'];
        yield 'elseif has no equivalent' => ['@elseif($x)', '@elseif($x)'];
        yield 'php directive' => ['@php echo 1; @endphp', '@php'];
        yield 'php tag' => ['<?php echo 1; ?>', '<?php'];
        yield 'unescaped echo' => ['{!! $reason !!}', '{!! $reason !!}'];
        yield 'loop over a call' => ['@foreach($invoice->items() as $item) x @endforeach', '@foreach($invoice->items() as $item)'];
    }

    #[DataProvider('provideTextThatOnlyLooksLikeBlade')]
    public function test_does_not_flag_text_that_only_looks_like_a_directive(string $content): void
    {
        $report = $this->scanner->scan($content);

        $this->assertTrue($report->isClean(), 'Flagged: '.implode(', ', [...$report->convertible, ...$report->manual]));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideTextThatOnlyLooksLikeBlade(): iterable
    {
        yield 'mail address' => ['Write to contact@example.com for help.'];
        yield 'css at-rule' => ['<style>@media (max-width: 600px) { body { margin: 0 } }</style>'];
        yield 'social handle' => ['Follow @clientxcms on the usual places.'];
        yield 'price with currency' => ['Total: 49,99 EUR'];
    }

    public function test_closing_directives_are_convertible(): void
    {
        $report = $this->scanner->scan('@if($ip) a @else b @endif @foreach($a->b as $c) d @endforeach');

        $this->assertSame([], $report->manual);
        $this->assertContains('@else', $report->convertible);
        $this->assertContains('@endif', $report->convertible);
        $this->assertContains('@endforeach', $report->convertible);
    }

    public function test_reports_each_construct_once(): void
    {
        $report = $this->scanner->scan('{{ $a }} {{ $a }} {{ $a }}');

        $this->assertSame(['{{ $a }}'], $report->convertible);
    }

    /**
     * The shipped invoice template, as stored today. Mixed verdict on purpose:
     * the loop converts, the price helper needs a prepared field.
     */
    public function test_reports_a_real_shipped_template(): void
    {
        $template = <<<'BLADE'
        <strong>Total</strong>: {{ formatted_price($invoice->total, $invoice->currency) }} <br/>
        @foreach($invoice->items as $item)
        <strong>Nom</strong> : {{ $item->name }} <br/>
        <strong>Prix </strong> : {{ formatted_price($item->price(), $invoice->currency) }} <br/>
        @endforeach
        BLADE;

        $report = $this->scanner->scan($template);

        $this->assertSame('manual', $report->status());
        $this->assertContains('@foreach($invoice->items as $item)', $report->convertible);
        $this->assertContains('{{ $item->name }}', $report->convertible);
        $this->assertContains('{{ formatted_price($invoice->total, $invoice->currency) }}', $report->manual);
    }
}
