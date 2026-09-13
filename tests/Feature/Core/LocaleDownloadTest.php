<?php

namespace Tests\Feature\Core;

use App\Services\Core\LocaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LocaleDownloadTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, string> */
    private array $langBackup = [];

    /**
     * downloadFiles() imports into lang/, so the suite would overwrite whatever
     * translations the developer has locally.
     */
    private bool $langBackupTaken = false;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (glob(base_path('lang/en/*.php')) ?: [] as $file) {
            $this->langBackup[$file] = (string) file_get_contents($file);
        }
        $this->langBackupTaken = true;
    }

    protected function tearDown(): void
    {
        // Never clean up on a half-built backup: it would delete tracked files
        if ($this->langBackupTaken) {
            foreach (glob(base_path('lang/en/*.php')) ?: [] as $file) {
                if (! array_key_exists($file, $this->langBackup)) {
                    @unlink($file);
                }
            }
            foreach ($this->langBackup as $file => $content) {
                file_put_contents($file, $content);
            }
        }
        parent::tearDown();
    }

    private function payload(): string
    {
        return json_encode([
            'language' => 'English',
            'lang.fr.a11y' => ['skip_to_content' => 'Skip to content'],
        ]);
    }

    /**
     * locales.json and the translation files live on the same host, so they
     * have to be faked apart or the locale list gets a translation file.
     */
    private function fakeHosting(mixed $translations): void
    {
        Cache::forget('locales');
        Http::fake([
            '*/locales.json' => Http::response(file_get_contents(resource_path('locales.json')), 200),
            '*/translations/*' => $translations,
            'api.github.com/*' => Http::response('should not be called', 500),
        ]);
    }

    public function test_it_downloads_translations_from_raw_hosting_not_the_rest_api(): void
    {
        $this->fakeHosting(Http::response($this->payload(), 200));

        LocaleService::downloadFiles('en_GB');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'raw.githubusercontent.com')
            && str_contains($request->url(), 'translations/en.json'));
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'api.github.com'));
    }

    public function test_it_stores_the_body_as_is_without_base64_decoding(): void
    {
        $this->fakeHosting(Http::response($this->payload(), 200));

        LocaleService::downloadFiles('en_GB');

        $this->assertSame('Skip to content', __('a11y.skip_to_content', [], 'en'));
    }

    public function test_it_refuses_a_response_that_is_not_a_translation_file(): void
    {
        $this->fakeHosting(Http::response('<html>rate limited</html>', 200));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/did not return a translation file/');
        LocaleService::downloadFiles('en_GB');
    }

    public function test_it_reports_the_status_code_when_the_download_fails(): void
    {
        $this->fakeHosting(Http::response('', 403));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Status code: 403/');
        LocaleService::downloadFiles('en_GB');
    }

    public function test_the_locale_list_falls_back_to_the_bundled_file_when_the_host_is_unreachable(): void
    {
        Cache::forget('locales');
        Http::fake(['*' => Http::response('<html>error</html>', 200)]);

        $locales = LocaleService::getLocalesFromAPI();

        $this->assertArrayHasKey('en_GB', $locales->toArray());
    }
}
