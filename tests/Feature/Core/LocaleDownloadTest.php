<?php

namespace Tests\Feature\Core;

use App\Providers\AppServiceProvider;
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
        return json_encode(['skip_to_content' => 'Skip to content']);
    }

    /**
     * The per-module download hits translations/<locale>/<module>.json for
     * every module fr has, so the fake must answer any of them the same way.
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

    public function test_it_downloads_from_the_version_branch_not_the_rest_api(): void
    {
        $this->fakeHosting(Http::response($this->payload(), 200));

        LocaleService::downloadFiles('en_GB');

        $expectedBranch = 'v'.AppServiceProvider::VERSION;
        Http::assertSent(fn ($request) => str_contains($request->url(), 'raw.githubusercontent.com')
            && str_contains($request->url(), "/{$expectedBranch}/translations/en/")
        );
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'api.github.com'));
    }

    public function test_it_stores_the_body_as_is_without_base64_decoding(): void
    {
        $this->fakeHosting(Http::response($this->payload(), 200));

        LocaleService::downloadFiles('en_GB');

        $this->assertSame('Skip to content', __('a11y.skip_to_content', [], 'en'));
    }

    public function test_it_prunes_a_module_en_has_that_fr_no_longer_does(): void
    {
        $staleModule = base_path('lang/en/__stale_test_module.php');
        file_put_contents($staleModule, "<?php\n\nreturn ['x' => 'y'];\n");

        try {
            $this->fakeHosting(Http::response($this->payload(), 200));
            LocaleService::downloadFiles('en_GB');

            $this->assertFileDoesNotExist($staleModule);
        } finally {
            @unlink($staleModule);
        }
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
