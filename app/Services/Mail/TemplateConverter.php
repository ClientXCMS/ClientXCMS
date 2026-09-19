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
 * Rewrites a Blade-era template into the closed grammar.
 *
 * Only translates what has one obvious equivalent. Anything else is left exactly
 * as it was and reported, because a converter that guesses produces mail nobody
 * proof-reads.
 */
class TemplateConverter
{
    private const VARIABLE = '\$([A-Za-z_]\w*)((?:->[A-Za-z_]\w*)*)';

    public function __construct(private readonly LegacySyntaxScanner $scanner) {}

    public function convert(string $template): TemplateConversion
    {
        $untouched = $this->scanner->scan($template)->manual;

        // Read the loop bindings first: rewriting the directives removes them.
        $items = $this->loopVariables($template);
        $converted = $this->rewriteExpressions($this->rewriteDirectives($template), $items);

        return new TemplateConversion($template, $converted, $untouched);
    }

    /**
     * Walks conditionals and loops keeping a stack, because a closing tag has to
     * repeat the path its opening tag used.
     */
    private function rewriteDirectives(string $template): string
    {
        $stack = [];

        return (string) preg_replace_callback(
            // No lookbehind: a directive often sits right after text. The closed
            // list plus the word boundary is what keeps a mail address out.
            // The optional space lives inside the group: outside it, a directive
            // without arguments would eat the space that belongs to the content.
            '/@(if|foreach|else|endif|endforeach)\b(\s*\((?:[^()]|\([^()]*\))*\))?/i',
            function (array $match) use (&$stack): string {
                $name = strtolower($match[1]);
                $arguments = trim(trim($match[2] ?? ''), '()');

                return match ($name) {
                    'if' => $this->openCondition($arguments, $stack),
                    'foreach' => $this->openLoop($arguments, $stack),
                    'else' => $this->invertTop($stack),
                    'endif', 'endforeach' => $this->close($stack),
                    default => $match[0],
                };
            },
            $template,
        ) ?: $template;
    }

    /**
     * @param  list<array{path: string, item: string|null}>  $stack
     */
    private function openCondition(string $arguments, array &$stack): string
    {
        $path = $this->pathOf(trim($arguments));
        if ($path === null) {
            $stack[] = ['path' => null, 'item' => null];

            return '@if('.$arguments.')';
        }
        $stack[] = ['path' => $path, 'item' => null];

        return '{{#'.$path.'}}';
    }

    /**
     * @param  list<array{path: string, item: string|null}>  $stack
     */
    private function openLoop(string $arguments, array &$stack): string
    {
        if (! preg_match('/^\s*'.self::VARIABLE.'\s+as\s+\$([A-Za-z_]\w*)\s*$/', $arguments, $parts)) {
            $stack[] = ['path' => null, 'item' => null];

            return '@foreach('.$arguments.')';
        }
        $path = $this->join($parts[1], $parts[2]);
        $stack[] = ['path' => $path, 'item' => $parts[3]];

        return '{{#'.$path.'}}';
    }

    /**
     * @param  list<array{path: string, item: string|null}>  $stack
     */
    private function invertTop(array &$stack): string
    {
        $current = end($stack);
        if ($current === false || $current['path'] === null) {
            return '@else';
        }

        return '{{/'.$current['path'].'}}{{^'.$current['path'].'}}';
    }

    /**
     * @param  list<array{path: string, item: string|null}>  $stack
     */
    private function close(array &$stack): string
    {
        $current = array_pop($stack);
        if ($current === null || $current['path'] === null) {
            return '@endif';
        }

        return '{{/'.$current['path'].'}}';
    }

    /**
     * Loop variables lose their prefix: inside a section the item is the context.
     *
     * @param  list<string>  $items  names bound by a loop in the original template
     */
    private function rewriteExpressions(string $template, array $items): string
    {
        return (string) preg_replace_callback(
            '/\{\{\s*'.self::VARIABLE.'\s*\}\}/',
            function (array $match) use ($items): string {
                $root = $match[1];
                if (in_array($root, $items, true)) {
                    $tail = trim(str_replace('->', '.', $match[2]), '.');

                    return $tail === '' ? '{{ . }}' : '{{ '.$tail.' }}';
                }

                return '{{ '.$this->join($root, $match[2]).' }}';
            },
            $template,
        ) ?: $template;
    }

    /**
     * @return list<string> names bound by a converted loop
     */
    private function loopVariables(string $template): array
    {
        preg_match_all('/@foreach\s*\(\s*'.self::VARIABLE.'\s+as\s+\$([A-Za-z_]\w*)\s*\)/', $template, $matches);

        return array_values(array_unique($matches[3] ?? []));
    }

    private function pathOf(string $expression): ?string
    {
        if (! preg_match('/^'.self::VARIABLE.'$/', $expression, $parts)) {
            return null;
        }

        return $this->join($parts[1], $parts[2]);
    }

    private function join(string $root, string $chain): string
    {
        return rtrim($root.str_replace('->', '.', $chain), '.');
    }
}
