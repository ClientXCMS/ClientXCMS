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

use App\Services\Mail\TemplateRenderer;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TemplateRendererTest extends TestCase
{
    private TemplateRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = new TemplateRenderer;
    }

    public function test_keeps_plain_text_untouched(): void
    {
        $this->assertSame('Hello there.', $this->renderer->render('Hello there.', []));
    }

    public function test_keeps_template_markup_untouched(): void
    {
        $template = '<strong>Total</strong>: <br/>';

        $this->assertSame($template, $this->renderer->render($template, []));
    }

    public function test_inserts_a_field(): void
    {
        $this->assertSame('Hello Alex.', $this->renderer->render('Hello {{ firstname }}.', ['firstname' => 'Alex']));
    }

    public function test_walks_a_dotted_path(): void
    {
        $data = ['ticket' => ['customer' => ['full_name' => 'Alex M']]];

        $this->assertSame('Alex M', $this->renderer->render('{{ ticket.customer.full_name }}', $data));
    }

    public function test_renders_an_unknown_field_as_nothing(): void
    {
        $this->assertSame('Hi .', $this->renderer->render('Hi {{ missing }}.', []));
    }

    public function test_escapes_the_values_it_inserts(): void
    {
        $output = $this->renderer->render('{{ name }}', ['name' => '<script>alert(1)</script>']);

        $this->assertSame('&lt;script&gt;alert(1)&lt;/script&gt;', $output);
    }

    public function test_repeats_a_section_over_a_list(): void
    {
        $data = ['items' => [['name' => 'A'], ['name' => 'B']]];

        $this->assertSame('A,B,', $this->renderer->render('{{#items}}{{ name }},{{/items}}', $data));
    }

    public function test_skips_a_section_whose_list_is_empty(): void
    {
        $this->assertSame('', $this->renderer->render('{{#items}}x{{/items}}', ['items' => []]));
    }

    public function test_renders_a_section_once_when_the_field_is_true(): void
    {
        $this->assertSame('yes', $this->renderer->render('{{#paid}}yes{{/paid}}', ['paid' => true]));
    }

    public function test_skips_a_section_when_the_field_is_false(): void
    {
        $this->assertSame('', $this->renderer->render('{{#paid}}yes{{/paid}}', ['paid' => false]));
    }

    public function test_renders_an_inverted_section_when_the_field_is_empty(): void
    {
        $template = '{{#paid}}yes{{/paid}}{{^paid}}no{{/paid}}';

        $this->assertSame('no', $this->renderer->render($template, ['paid' => false]));
        $this->assertSame('yes', $this->renderer->render($template, ['paid' => true]));
    }

    public function test_reads_an_outer_field_from_inside_a_section(): void
    {
        $data = ['currency' => 'EUR', 'items' => [['name' => 'A'], ['name' => 'B']]];

        $this->assertSame('A EUR|B EUR|', $this->renderer->render('{{#items}}{{ name }} {{ currency }}|{{/items}}', $data));
    }

    public function test_inserts_the_current_item_of_a_scalar_list(): void
    {
        $this->assertSame('a;b;', $this->renderer->render('{{#tags}}{{ . }};{{/tags}}', ['tags' => ['a', 'b']]));
    }

    public function test_enters_a_section_holding_a_single_record(): void
    {
        $data = ['invoice' => ['id' => '42']];

        $this->assertSame('42', $this->renderer->render('{{#invoice}}{{ id }}{{/invoice}}', $data));
    }

    public function test_nests_sections(): void
    {
        $data = ['orders' => [['lines' => [['sku' => 'X'], ['sku' => 'Y']]]]];

        $this->assertSame('XY', $this->renderer->render('{{#orders}}{{#lines}}{{ sku }}{{/lines}}{{/orders}}', $data));
    }

    /**
     * The security contract: nothing outside the grammar is interpreted.
     */
    #[DataProvider('provideSyntaxOutsideTheGrammar')]
    public function test_leaves_every_other_syntax_as_literal_text(string $template): void
    {
        $this->assertSame($template, $this->renderer->render($template, ['reason' => 'ignored']));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideSyntaxOutsideTheGrammar(): iterable
    {
        yield 'php open tag' => ['<?php phpinfo(); ?>'];
        yield 'blade raw echo' => ['{!! $reason !!}'];
        yield 'blade php directive' => ['@php phpinfo(); @endphp'];
        yield 'blade condition' => ['@if($reason) shown @endif'];
        yield 'blade loop' => ['@foreach($items as $item) {{ $item->name }} @endforeach'];
        yield 'blade variable' => ['{{ $reason }}'];
        yield 'method call' => ['{{ invoice.total() }}'];
        yield 'function call' => ['{{ formatted_price(1, 2) }}'];
        yield 'array callable' => ['{{ [Foo::class, chr(98)]() }}'];
        yield 'spliced function name' => ['{{ phpin)(fo() }}'];
        yield 'config read' => ['{{ config(chr(97)) }}'];
    }

    /**
     * Fails if this renderer is ever swapped back for Blade: the same string is
     * evaluated by one and left alone by the other. Harmless expression on purpose.
     */
    public function test_does_not_evaluate_what_blade_would_have_evaluated(): void
    {
        $template = '{{ strtoupper(chr(97)) }}';

        $this->assertSame('A', \Blade::render($template));
        $this->assertSame($template, $this->renderer->render($template, []));
    }

    public function test_does_not_treat_a_triple_brace_as_raw_output(): void
    {
        $output = $this->renderer->render('{{{ name }}}', ['name' => '<b>x</b>']);

        $this->assertSame('{&lt;b&gt;x&lt;/b&gt;}', $output);
    }

    public function test_shows_an_unclosed_section_instead_of_breaking_the_mail(): void
    {
        $output = $this->renderer->render('before {{#items}}body', ['items' => [['name' => 'A']]]);

        $this->assertSame('before {{#items}}body', $output);
    }

    public function test_shows_a_stray_closing_tag(): void
    {
        $this->assertSame('a {{/items}}b', $this->renderer->render('a {{/items}}b', []));
    }

    public function test_refuses_an_object_in_the_context(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/holds a/');

        $this->renderer->render('{{ invoice.id }}', ['invoice' => new \stdClass]);
    }

    public function test_refuses_a_closure_in_the_context(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->renderer->render('{{ total }}', ['total' => fn () => 'boom']);
    }

    public function test_refuses_an_object_buried_inside_a_list(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->renderer->render('{{#items}}{{ name }}{{/items}}', ['items' => [['name' => new \stdClass]]]);
    }
}
