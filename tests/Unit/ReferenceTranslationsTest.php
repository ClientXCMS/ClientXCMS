<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ReferenceTranslationsTest extends TestCase
{
    private const REFERENCE_LOCALES = ['fr', 'en'];

    /**
     * @return array<int, string>
     */
    private function referenceFiles(string $locale): array
    {
        return glob(dirname(__DIR__, 2)."/lang/{$locale}/*.php") ?: [];
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function provideReferenceLocales(): iterable
    {
        foreach (self::REFERENCE_LOCALES as $locale) {
            yield $locale => [$locale];
        }
    }

    #[DataProvider('provideReferenceLocales')]
    public function test_every_reference_file_returns_a_non_empty_array(string $locale): void
    {
        $files = $this->referenceFiles($locale);
        $this->assertNotEmpty($files, "the {$locale} reference translations are missing");

        foreach ($files as $file) {
            $content = require $file;
            $this->assertIsArray($content, basename($file).' must return an array');
            $this->assertNotEmpty($content, basename($file).' must not be empty');
        }
    }

    /**
     * The translation repository stores placeholders as {_name} and the import
     * converts them to Laravel's :name. A file committed without that step
     * would render the placeholder literally to the user.
     */
    #[DataProvider('provideReferenceLocales')]
    public function test_no_placeholder_is_left_in_the_translation_repository_format(string $locale): void
    {
        $offenders = [];

        foreach ($this->referenceFiles($locale) as $file) {
            foreach (explode("\n", (string) file_get_contents($file)) as $number => $line) {
                if (preg_match('/\{_\w+\}/', $line)) {
                    $offenders[] = "lang/{$locale}/".basename($file).':'.($number + 1);
                }
            }
        }

        $this->assertSame([], $offenders, 'unconverted placeholders: '.implode(', ', $offenders));
    }
}
