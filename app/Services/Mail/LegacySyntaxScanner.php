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

namespace App\Services\Mail;

/**
 * Reads a stored template and reports what still uses the Blade syntax.
 * Read-only: it never rewrites anything.
 */
class LegacySyntaxScanner
{
    /** A bare variable path, the only expression shape a closed grammar can carry. */
    private const PATH = '\$[A-Za-z_]\w*(?:->[A-Za-z_]\w*)*';

    private const DIRECTIVE = '/(?<![\w.@])@([A-Za-z_]\w*)\s*(\((?:[^()]|\((?:[^()]|\([^()]*\))*\))*\))?/';

    private const TRANSLATABLE_DIRECTIVES = ['else', 'endif', 'endforeach'];

    /**
     * Closed list on purpose. A generic "@word" match would flag mail addresses
     * and CSS at-rules, and a diagnostic that cries wolf gets ignored. A Blade
     * directive missing from this list renders as literal text, which is visible
     * and harmless, where a false alarm costs trust.
     */
    private const BLADE_DIRECTIVES = [
        'if', 'elseif', 'else', 'endif', 'unless', 'endunless', 'isset', 'endisset', 'empty', 'endempty',
        'foreach', 'endforeach', 'forelse', 'endforelse', 'for', 'endfor', 'while', 'endwhile',
        'php', 'endphp', 'include', 'includeif', 'includewhen', 'includefirst', 'each', 'extends',
        'section', 'endsection', 'yield', 'parent', 'component', 'endcomponent', 'slot', 'endslot',
        'push', 'endpush', 'prepend', 'endprepend', 'stack', 'once', 'endonce', 'verbatim', 'endverbatim',
        'switch', 'case', 'default', 'endswitch', 'break', 'continue', 'auth', 'endauth', 'guest', 'endguest',
        'can', 'endcan', 'cannot', 'endcannot', 'error', 'enderror', 'json', 'dd', 'dump', 'lang', 'csrf', 'method',
    ];

    public function scan(string $content): LegacySyntaxReport
    {
        $convertible = [];
        $manual = [];

        foreach ($this->rawTags($content) as $tag) {
            $manual[] = $tag;
        }
        foreach ($this->directives($content) as $directive => $isConvertible) {
            $isConvertible ? $convertible[] = $directive : $manual[] = $directive;
        }
        foreach ($this->expressions($content) as $expression => $isConvertible) {
            $isConvertible ? $convertible[] = $expression : $manual[] = $expression;
        }

        return new LegacySyntaxReport(array_values(array_unique($convertible)), array_values(array_unique($manual)));
    }

    /**
     * PHP tags and unescaped echoes: never translatable, always a decision.
     *
     * @return list<string>
     */
    private function rawTags(string $content): array
    {
        $found = [];
        if (preg_match_all('/<\?(?:php|=)?|\?>/i', $content, $matches)) {
            $found = array_merge($found, $matches[0]);
        }
        if (preg_match_all('/\{!!.*?!!\}/s', $content, $matches)) {
            $found = array_merge($found, $matches[0]);
        }

        return $found;
    }

    /**
     * @return array<string, bool> construct => convertible
     */
    private function directives(string $content): array
    {
        preg_match_all(self::DIRECTIVE, $content, $matches, PREG_SET_ORDER);

        $found = [];
        $open = [];
        foreach ($matches as $match) {
            $name = strtolower($match[1]);
            if (! in_array($name, self::BLADE_DIRECTIVES, true)) {
                continue;
            }
            $arguments = trim($match[2] ?? '', '()');
            $convertible = match ($name) {
                'if' => (bool) preg_match('/^\s*'.self::PATH.'\s*$/', $arguments),
                'foreach' => (bool) preg_match('/^\s*'.self::PATH.'\s+as\s+\$[A-Za-z_]\w*\s*$/', $arguments),
                // A closing tag can only be translated if its opening tag was.
                default => in_array($name, self::TRANSLATABLE_DIRECTIVES, true) && (end($open) ?: false),
            };
            if (in_array($name, ['if', 'foreach'], true)) {
                $open[] = $convertible;
            } elseif (in_array($name, ['endif', 'endforeach'], true)) {
                array_pop($open);
            }
            // Same construct seen twice with different verdicts: keep the strict one.
            $key = trim($match[0]);
            $found[$key] = ($found[$key] ?? true) && $convertible;
        }

        return $found;
    }

    /**
     * Only a bare path converts. An expression, a call or a comparison needs a
     * prepared field, which is a decision rather than a translation.
     *
     * @return array<string, bool> construct => convertible
     */
    private function expressions(string $content): array
    {
        preg_match_all('/\{\{(.*?)\}\}/s', $content, $matches, PREG_SET_ORDER);

        $found = [];
        foreach ($matches as $match) {
            $expression = trim($match[1]);
            if (! str_contains($expression, '$')) {
                continue; // already the closed grammar, or plain text
            }
            $found[trim($match[0])] = (bool) preg_match('/^'.self::PATH.'$/', $expression);
        }

        return $found;
    }
}
