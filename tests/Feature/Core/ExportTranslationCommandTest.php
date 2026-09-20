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
     * @return iterable<string, array{0: string}>
     */
    public static function provideLocales(): iterable
    {
        yield 'fr' => ['fr'];
        yield 'en' => ['en'];
    }

    #[DataProvider('provideLocales')]
    public function test_export_produces_one_json_file_per_module_with_converted_placeholders(string $locale): void
    {
        $outputDirectory = storage_path("export-test-{$locale}");

        try {
            Artisan::call('translations:export', ['--locale' => $locale, '--path' => $outputDirectory]);

            $this->assertFileExists("{$outputDirectory}/provisioning.json");

            $content = json_decode(File::get("{$outputDirectory}/provisioning.json"), true);
            $this->assertArrayNotHasKey('language', $content, 'per-module files carry no locale metadata, only content');

            $nameserver = $content['domain_manager']['nameserver'];
            $this->assertStringNotContainsString(':number', $nameserver, 'the laravel placeholder must be converted');
            $this->assertStringContainsString('{_number}', $nameserver);
        } finally {
            File::deleteDirectory($outputDirectory);
        }
    }

    public function test_export_defaults_to_french(): void
    {
        $outputDirectory = storage_path('fr');

        try {
            Artisan::call('translations:export');

            $this->assertFileExists("{$outputDirectory}/global.json");
        } finally {
            File::deleteDirectory($outputDirectory);
        }
    }

    public function test_export_reports_an_error_for_an_unknown_locale(): void
    {
        $outputDirectory = storage_path('export-test-xx');

        try {
            Artisan::call('translations:export', ['--locale' => 'xx', '--path' => $outputDirectory]);

            $this->assertStringContainsString('No xx translations found', Artisan::output());
            $this->assertDirectoryDoesNotExist($outputDirectory);
        } finally {
            File::deleteDirectory($outputDirectory);
        }
    }
}
