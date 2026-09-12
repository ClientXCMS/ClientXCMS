<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;

class NoDebugCallsLeftTest extends TestCase
{
    /**
     * A single dd() left in a service interrupts the request and dumps internals
     * to whoever triggered it, so it must never reach a release.
     */
    public function test_no_debug_call_is_left_in_the_application_code(): void
    {
        $offenders = [];
        $finder = (new Finder)->files()->in(dirname(__DIR__, 2).'/app')->name('*.php');

        foreach ($finder as $file) {
            foreach (explode("\n", (string) $file->getContents()) as $number => $line) {
                if (preg_match('/^\s*(\*|\/\/|#)/', $line)) {
                    continue;
                }
                // Quotes excluded: Section.php lists these names as forbidden strings
                if (preg_match('/(?<![\w$>:\\\\\'"])(dd|dump|var_dump)\s*\(/', $line)) {
                    $offenders[] = 'app/'.$file->getRelativePathname().':'.($number + 1);
                }
            }
        }

        $this->assertSame([], $offenders, 'debug calls left behind: '.implode(', ', $offenders));
    }
}
