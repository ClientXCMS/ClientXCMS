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

namespace Tests\Feature\Admin\Store;

use App\Http\Requests\Store\StoreProductRequest;
use App\Http\Requests\Store\UpdateProductRequest;
use App\Services\Content\SafeHtml;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Two admin product cards render the description without escaping, so what is
 * stored has to be safe already.
 *
 * These assertions used to read the request classes and look for a rule name
 * with a regular expression, which passes whether or not anything is actually
 * cleaned. They now put markup through the request and look at what comes out.
 */
class ProductDescriptionXssTest extends TestCase
{
    #[DataProvider('provideMarkupThatMustNotSurvive')]
    public function test_a_stored_description_never_carries_active_markup(string $hostile, string $trace): void
    {
        foreach ([StoreProductRequest::class, UpdateProductRequest::class] as $class) {
            $request = $class::create('/', 'POST', ['description' => 'Bonjour '.$hostile, 'pricing' => []]);
            $request->setContainer(app())->setRedirector(app('redirect'));
            $this->callPrepare($request);

            $this->assertStringNotContainsString($trace, (string) $request->input('description'), $class);
        }
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideMarkupThatMustNotSurvive(): iterable
    {
        yield 'script element' => ['<script>alert(1)</script>', '<script'];
        yield 'event attribute' => ['<img src=x onerror=alert(1)>', 'onerror'];
        yield 'javascript url' => ['<a href="javascript:alert(1)">x</a>', 'javascript:'];
        yield 'php tag' => ['<?php phpinfo(); ?>', '<?php'];
        yield 'iframe' => ['<iframe src="//evil.test"></iframe>', '<iframe'];
    }

    public function test_ordinary_description_markup_is_kept(): void
    {
        $request = StoreProductRequest::create('/', 'POST', [
            'description' => 'Hébergement <strong>NVMe</strong> avec <a href="https://exemple.test">garantie</a>.',
            'pricing' => [],
        ]);
        $request->setContainer(app())->setRedirector(app('redirect'));
        $this->callPrepare($request);

        $stored = (string) $request->input('description');
        $this->assertStringContainsString('<strong>NVMe</strong>', $stored);
        $this->assertStringContainsString('href="https://exemple.test"', $stored);
    }

    /**
     * The invoice tab escapes rather than sanitises, which is right there: the
     * line description is plain text. Guards that it stays that way.
     */
    public function test_the_invoice_tab_escapes_its_line_description(): void
    {
        $src = file_get_contents(resource_path('views/admin/core/invoices/tabs/show.blade.php'));

        $this->assertStringNotContainsString('nl2br($item->description)', $src);
        $this->assertStringContainsString('nl2br(e($item->description))', $src);
    }

    public function test_the_sanitizer_leaves_plain_text_alone(): void
    {
        $this->assertSame('Sans balise', app(SafeHtml::class)->sanitize('Sans balise'));
    }

    private function callPrepare(object $request): void
    {
        $method = new \ReflectionMethod($request, 'prepareForValidation');
        $method->invoke($request);
    }
}
