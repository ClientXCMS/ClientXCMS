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

namespace Tests\Feature\Core;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ExportTranslationCommandTest extends TestCase
{
    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function provideLocales(): iterable
    {
        yield 'fr' => ['fr', 'Français'];
        yield 'en' => ['en', 'English'];
    }

    #[DataProvider('provideLocales')]
    public function test_export_produces_the_requested_locale_with_converted_placeholders(string $locale, string $languageName): void
    {
        $path = "export-test-{$locale}.json";
        $storagePath = storage_path($path);

        try {
            Artisan::call('translations:export', ['--locale' => $locale, '--path' => $path]);

            $this->assertFileExists($storagePath);

            $content = json_decode(File::get($storagePath), true);

            $this->assertSame($languageName, $content['language']);
            $this->assertArrayHasKey("lang.{$locale}.provisioning", $content);

            $nameserver = $content["lang.{$locale}.provisioning"]['domain_manager']['nameserver'];
            $this->assertStringNotContainsString(':number', $nameserver, 'the laravel placeholder must be converted');
            $this->assertStringContainsString('{_number}', $nameserver);
        } finally {
            File::delete($storagePath);
        }
    }

    public function test_export_defaults_to_french(): void
    {
        $storagePath = storage_path('fr.json');

        try {
            Artisan::call('translations:export');

            $content = json_decode(File::get($storagePath), true);

            $this->assertSame('Français', $content['language']);
        } finally {
            File::delete($storagePath);
        }
    }
}
