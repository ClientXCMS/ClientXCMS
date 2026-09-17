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
use App\Services\Mail\TemplateConverter;
use App\Services\Mail\TemplateRenderer;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TemplateConverterTest extends TestCase
{
    private TemplateConverter $converter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->converter = new TemplateConverter(new LegacySyntaxScanner);
    }

    #[DataProvider('provideRewrites')]
    public function test_rewrites_a_construct(string $before, string $after): void
    {
        $this->assertSame($after, $this->converter->convert($before)->after);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideRewrites(): iterable
    {
        yield 'bare variable' => ['Hello {{ $reason }}', 'Hello {{ reason }}'];
        yield 'property path' => ['{{ $ticket->customer->fullName }}', '{{ ticket.customer.fullName }}'];
        yield 'path without spaces' => ['{{ $invoice->id}}', '{{ invoice.id }}'];
        yield 'condition' => ['@if($ip)x@endif', '{{#ip}}x{{/ip}}'];
        yield 'condition on a path' => ['@if($service->expires_at)x@endif', '{{#service.expires_at}}x{{/service.expires_at}}'];
        yield 'condition with else' => ['@if($ip)a@else b@endif', '{{#ip}}a{{/ip}}{{^ip}} b{{/ip}}'];
        yield 'loop' => ['@foreach($invoice->items as $item)x@endforeach', '{{#invoice.items}}x{{/invoice.items}}'];
        yield 'loop item loses its prefix' => [
            '@foreach($invoice->items as $item){{ $item->name }}@endforeach',
            '{{#invoice.items}}{{ name }}{{/invoice.items}}',
        ];
        yield 'plain text is untouched' => ['<strong>Total</strong><br/>', '<strong>Total</strong><br/>'];
        yield 'a mail address is not a directive' => ['Write to contact@example.com', 'Write to contact@example.com'];
        yield 'a domain starting like a directive is left alone' => ['see x@iffy.example.com', 'see x@iffy.example.com'];
        yield 'a directive glued to text still converts' => ['@if($ip)x@endif', '{{#ip}}x{{/ip}}'];
    }

    public function test_leaves_alone_what_it_cannot_translate(): void
    {
        $before = '@if($reward_amount > 0)x@endif';

        $conversion = $this->converter->convert($before);

        $this->assertSame($before, $conversion->after);
        $this->assertFalse($conversion->isComplete());
        $this->assertContains('@if($reward_amount > 0)', $conversion->untouched);
    }

    public function test_leaves_a_helper_call_alone_and_reports_it(): void
    {
        $conversion = $this->converter->convert('{{ formatted_price($invoice->total, $invoice->currency) }}');

        $this->assertSame('{{ formatted_price($invoice->total, $invoice->currency) }}', $conversion->after);
        $this->assertFalse($conversion->isComplete());
    }

    public function test_reports_a_template_that_needed_nothing(): void
    {
        $conversion = $this->converter->convert('{{ invoice.total }}');

        $this->assertFalse($conversion->changed());
        $this->assertTrue($conversion->isComplete());
    }

    /**
     * The whole point: what comes out is understood by the renderer, and the
     * values land where the original put them.
     */
    public function test_the_result_renders_with_the_expected_values(): void
    {
        $before = <<<'BLADE'
        @if($invoice->paid)Payée@else En attente@endif
        @foreach($invoice->items as $item){{ $item->name }};@endforeach
        BLADE;

        $after = $this->converter->convert($before)->after;
        $data = ['invoice' => ['paid' => false, 'items' => [['name' => 'A'], ['name' => 'B']]]];

        $rendered = (new TemplateRenderer)->render($after, $data);

        $this->assertStringContainsString('En attente', $rendered);
        $this->assertStringNotContainsString('Payée', $rendered);
        $this->assertStringContainsString('A;B;', $rendered);
    }

    /**
     * Converting twice must not change anything the second time, otherwise a
     * migration that runs again corrupts what it already fixed.
     */
    public function test_converting_an_already_converted_template_changes_nothing(): void
    {
        $once = $this->converter->convert('@foreach($invoice->items as $item){{ $item->name }}@endforeach')->after;
        $twice = $this->converter->convert($once)->after;

        $this->assertSame($once, $twice);
    }
}
