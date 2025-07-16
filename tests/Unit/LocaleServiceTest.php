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
 * Year: 2025
 */
namespace Tests\Unit\Services\Core;

use App\Models\Account\Customer;
use App\Models\Admin\Setting;
use App\Services\Core\LocaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Tests\TestCase;

class LocaleServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_valid_locale_names()
    {
        Cache::shouldReceive('rememberForever')
            ->once()
            ->andReturn(collect([
                'en_GB' => ['name' => 'English (UK)', 'is_enabled' => true, 'is_downloaded' => true, 'is_default' => false],
                'fr_FR' => ['name' => 'Français', 'is_enabled' => true, 'is_downloaded' => true, 'is_default' => false],
            ]));

        $names = LocaleService::getLocalesNames();

        $this->assertArrayHasKey('en_GB', $names);
        $this->assertEquals('English (UK)', $names['en_GB']);
    }

    public function test_it_fetches_current_locale_from_cookie()
    {
        Cookie::queue('locale', 'fr_FR');
        Cookie::shouldReceive('get')->with('locale')->andReturn('fr_FR');

        Cache::shouldReceive('rememberForever')
            ->andReturn(collect([
                'fr_FR' => ['name' => 'Français', 'is_enabled' => true, 'is_downloaded' => true, 'is_default' => false],
                'en_GB' => ['name' => 'English', 'is_enabled' => true, 'is_downloaded' => true, 'is_default' => true],
            ]));

        $locale = LocaleService::fetchCurrentLocale();

        $this->assertEquals('fr_FR', $locale);
    }

    public function test_it_sets_and_saves_locale()
    {
        $user = Customer::factory()->create(['locale' => 'en_GB']);

        $this->actingAs($user);

        $response = LocaleService::saveLocale('fr_FR');

        $user->refresh();
        $this->assertEquals('fr_FR', $user->locale);
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
    }

    public function test_it_validates_locale()
    {
        Cache::shouldReceive('rememberForever')
            ->andReturn(collect([
                'en_GB' => ['name' => 'English', 'is_enabled' => true, 'is_downloaded' => true, 'is_default' => true],
            ]));

        $this->assertTrue(LocaleService::isValideLocale('en_GB'));
        $this->assertFalse(LocaleService::isValideLocale('de_DE'));
    }

    public function test_it_toggles_locale()
    {
        Setting::updateSettings([
            'app_enabled_locales' => json_encode(['en_GB']),
            'app_default_locale' => 'en_GB',
        ]);
        LocaleService::toggleLocale('fr_FR');

        $setting = Setting::where('name', 'app_enabled_locales')->first();
        $this->assertStringContainsString('fr_FR', $setting->value);
    }

    public function test_it_handles_locale_download_failure()
    {
        $this->expectException(\Exception::class);

        Cache::shouldReceive('rememberForever')
            ->andReturn(collect([
                'en_GB' => ['key' => 'en_GB', 'name' => 'English', 'is_downloaded' => true, 'is_enabled' => true, 'is_default' => true],
            ]));

        LocaleService::downloadFiles('non_existing_locale');
    }
}
