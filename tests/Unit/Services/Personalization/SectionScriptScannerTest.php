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

namespace Tests\Unit\Services\Personalization;

use App\Services\Personalization\SectionScriptScanner;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SectionScriptScannerTest extends TestCase
{
    private SectionScriptScanner $scanner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scanner = new SectionScriptScanner;
    }

    #[DataProvider('provideHarmlessMarkup')]
    public function test_finds_nothing_in_ordinary_markup(string $html): void
    {
        $this->assertSame([], $this->scanner->scan($html));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideHarmlessMarkup(): iterable
    {
        yield 'empty' => [''];
        yield 'text' => ['Bienvenue chez nous.'];
        yield 'formatting' => ['<div class="hero"><h1>Titre</h1><p>Texte <strong>gras</strong></p></div>'];
        yield 'link' => ['<a href="https://example.com" title="on the site">Voir</a>'];
        yield 'image' => ['<img src="/img/logo.png" alt="Logo">'];
        yield 'inline style' => ['<p style="color: red">Rouge</p>'];
    }

    public function test_finds_a_script_element(): void
    {
        $this->assertSame(['<script> element'], $this->scanner->scan('<div><script>alert(1)</script></div>'));
    }

    public function test_finds_an_event_attribute(): void
    {
        $this->assertSame(['onerror attribute on <img>'], $this->scanner->scan('<img src=x onerror=alert(1)>'));
    }

    public function test_finds_a_javascript_url(): void
    {
        $this->assertSame(['javascript: url in href'], $this->scanner->scan('<a href="javascript:alert(1)">x</a>'));
    }

    public function test_ignores_case_and_leading_space_in_a_javascript_url(): void
    {
        $this->assertSame(['javascript: url in href'], $this->scanner->scan('<a href="  JavaScript:alert(1)">x</a>'));
    }

    public function test_reports_each_finding_once(): void
    {
        $findings = $this->scanner->scan('<script>a</script><script>b</script>');

        $this->assertSame(['<script> element'], $findings);
    }

    public function test_reports_several_kinds_at_once(): void
    {
        $findings = $this->scanner->scan('<script>a</script><a href="javascript:b" onclick="c">x</a>');

        $this->assertContains('<script> element', $findings);
        $this->assertContains('javascript: url in href', $findings);
        $this->assertContains('onclick attribute on <a>', $findings);
    }

    /**
     * An attribute whose name merely starts with "on" is still reported. Better a
     * named false alarm an operator can dismiss than a silent miss.
     */
    public function test_reports_an_attribute_whose_name_starts_with_on(): void
    {
        $this->assertNotEmpty($this->scanner->scan('<div once="1">x</div>'));
    }
}
