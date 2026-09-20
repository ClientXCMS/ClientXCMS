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
     * @return array<string, mixed>
     */
    private function flatten(array $array, string $prefix = ''): array
    {
        $flat = [];
        foreach ($array as $key => $value) {
            $path = $prefix === '' ? (string) $key : "{$prefix}.{$key}";
            if (is_array($value)) {
                $flat += $this->flatten($value, $path);
            } else {
                $flat[$path] = $value;
            }
        }

        return $flat;
    }

    /**
     * @return array<int, string>
     */
    private function placeholders(string $value): array
    {
        preg_match_all('/:[a-zA-Z_][a-zA-Z0-9_]*/', $value, $matches);
        $unique = array_unique($matches[0]);
        sort($unique);

        return $unique;
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

    /**
     * Reference locales must expose the exact same keys. A key present in one
     * but not the other ships either a raw key or a fallback string to users
     * of the other locale.
     */
    public function test_reference_locales_have_the_same_keys(): void
    {
        $offenders = [];

        foreach ($this->referenceFiles('fr') as $frFile) {
            $base = basename($frFile);
            $enFile = dirname($frFile, 2).'/en/'.$base;
            if (! file_exists($enFile)) {
                $offenders[] = "lang/en/{$base} is missing entirely";

                continue;
            }

            $frKeys = array_keys($this->flatten(require $frFile));
            $enKeys = array_keys($this->flatten(require $enFile));

            foreach (array_diff($frKeys, $enKeys) as $missing) {
                $offenders[] = "lang/en/{$base}: missing key {$missing}";
            }
            foreach (array_diff($enKeys, $frKeys) as $extra) {
                $offenders[] = "lang/en/{$base}: extra key {$extra} not in fr";
            }
        }

        $this->assertSame([], $offenders, "key parity broken:\n".implode("\n", $offenders));
    }

    /**
     * A translation must carry the same :placeholders as its reference string.
     * A machine translation that glues a placeholder to the surrounding word
     * (":count" becoming ":counttask") silently breaks the substitution and
     * ships the raw token to the user instead of the value.
     */
    public function test_reference_locales_have_matching_placeholders(): void
    {
        $offenders = [];

        foreach ($this->referenceFiles('fr') as $frFile) {
            $base = basename($frFile);
            $enFile = dirname($frFile, 2).'/en/'.$base;
            if (! file_exists($enFile)) {
                continue;
            }

            $fr = $this->flatten(require $frFile);
            $en = $this->flatten(require $enFile);

            foreach ($fr as $key => $value) {
                if (! isset($en[$key]) || ! is_string($value) || ! is_string($en[$key])) {
                    continue;
                }

                $frPlaceholders = $this->placeholders($value);
                $enPlaceholders = $this->placeholders($en[$key]);

                if ($frPlaceholders !== $enPlaceholders) {
                    $offenders[] = "lang/en/{$base}:{$key} fr=[".implode(',', $frPlaceholders).'] en=['.implode(',', $enPlaceholders).']';
                }
            }
        }

        $this->assertSame([], $offenders, "placeholder mismatch:\n".implode("\n", $offenders));
    }
}
