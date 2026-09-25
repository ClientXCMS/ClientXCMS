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

namespace Tests\Feature\Admin\Personalization;

use App\Models\Admin\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The footer shows on every public page and the themes render these two
 * settings without escaping, so what is stored has to be safe already.
 */
class FooterHtmlIsSanitizedTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::factory()->create();
    }

    private function submit(string $description, ?string $badge = null): void
    {
        $this->actingAs($this->admin, 'admin')->post(
            route('admin.personalization.bottom_menu'),
            ['theme_footer_description' => $description, 'theme_footer_topheberg' => $badge],
        );
    }

    #[DataProvider('provideMarkupThatMustNotSurvive')]
    public function test_active_markup_never_reaches_the_database(string $hostile, string $trace): void
    {
        $this->submit('Bonjour '.$hostile);

        $this->assertStringNotContainsString($trace, (string) setting('theme_footer_description'));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideMarkupThatMustNotSurvive(): iterable
    {
        yield 'script element' => ['<script>alert(1)</script>', '<script'];
        yield 'event attribute' => ['<img src=x onerror=alert(1)>', 'onerror'];
        yield 'javascript url' => ['<a href="javascript:alert(1)">x</a>', 'javascript:'];
        yield 'iframe' => ['<iframe src="//evil.test"></iframe>', '<iframe'];
        yield 'inline style' => ['<p style="width:expression(alert(1))">x</p>', 'expression'];
    }

    /**
     * A footer legitimately carries a link, a logo and some emphasis. Removing
     * the risk must not remove the feature.
     */
    public function test_ordinary_footer_markup_is_kept(): void
    {
        $this->submit('Hébergé avec <strong>soin</strong> par <a href="https://exemple.test">Exemple</a>.');

        $stored = (string) setting('theme_footer_description');
        $this->assertStringContainsString('<strong>soin</strong>', $stored);
        $this->assertStringContainsString('href="https://exemple.test"', $stored);
        $this->assertStringContainsString('Hébergé', $stored);
    }

    public function test_the_badge_field_is_cleaned_too(): void
    {
        $this->submit('Description', '<a href="https://top.test"><img src="/badge.png" alt="Badge"></a><script>alert(1)</script>');

        $stored = (string) setting('theme_footer_topheberg');
        $this->assertStringContainsString('<img src="/badge.png"', $stored);
        $this->assertStringNotContainsString('<script', $stored);
    }

    public function test_plain_text_goes_through_untouched(): void
    {
        $this->submit('CLIENTXCMS, hébergeur indépendant.');

        $this->assertSame('CLIENTXCMS, hébergeur indépendant.', (string) setting('theme_footer_description'));
    }
}
