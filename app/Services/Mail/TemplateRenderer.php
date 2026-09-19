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

use InvalidArgumentException;

/**
 * Renders staff-editable templates. The grammar below is the whole language:
 * anything else is literal text, so an editable template can never become code.
 */
class TemplateRenderer
{
    private const TAG = '/\{\{\s*(?<sigil>[#^\/]?)\s*(?<path>\.|[a-zA-Z_][a-zA-Z0-9_]*(?:\.[a-zA-Z_][a-zA-Z0-9_]*)*)\s*\}\}/';

    private const MAX_CONTEXT_DEPTH = 10;

    /**
     * @param  array<string, mixed>  $data  scalars and arrays only, no objects
     */
    public function render(string $template, array $data): string
    {
        $this->assertRenderable($data, 0);

        $tokens = $this->tokenize($template);
        $index = 0;

        return $this->renderNodes($this->parseNodes($tokens, $index, null), [$data]);
    }

    /**
     * Objects are refused rather than ignored: allowing one would reintroduce
     * method calls and property reads, which is the whole point of this class.
     *
     * @param  array<array-key, mixed>  $data
     */
    private function assertRenderable(array $data, int $depth): void
    {
        if ($depth > self::MAX_CONTEXT_DEPTH) {
            throw new InvalidArgumentException('Mail template context is nested deeper than '.self::MAX_CONTEXT_DEPTH.' levels.');
        }

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $this->assertRenderable($value, $depth + 1);

                continue;
            }
            if (is_object($value) || is_resource($value)) {
                throw new InvalidArgumentException(sprintf('Mail template context key "%s" holds a %s; only scalars and arrays are allowed.', $key, get_debug_type($value)));
            }
        }
    }

    /**
     * @return list<array{type: string, value?: string, sigil?: string, path?: string}>
     */
    private function tokenize(string $template): array
    {
        preg_match_all(self::TAG, $template, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

        $tokens = [];
        $offset = 0;
        foreach ($matches as $match) {
            [$tag, $position] = $match[0];
            if ($position > $offset) {
                $tokens[] = ['type' => 'text', 'value' => substr($template, $offset, $position - $offset)];
            }
            $tokens[] = ['type' => 'tag', 'sigil' => $match['sigil'][0], 'path' => $match['path'][0]];
            $offset = $position + strlen($tag);
        }
        if ($offset < strlen($template)) {
            $tokens[] = ['type' => 'text', 'value' => substr($template, $offset)];
        }

        return $tokens;
    }

    /**
     * @param  list<array<string, string>>  $tokens
     * @return list<array<string, mixed>>
     */
    private function parseNodes(array $tokens, int &$index, ?string $closing): array
    {
        $nodes = [];

        while ($index < count($tokens)) {
            $token = $tokens[$index];
            if ($token['type'] === 'text') {
                $nodes[] = $token;
                $index++;

                continue;
            }
            if ($token['sigil'] === '/') {
                if ($token['path'] === $closing) {
                    $index++;

                    return $nodes;
                }
                // Stray closing tag: keep it visible instead of breaking the mail.
                $nodes[] = ['type' => 'text', 'value' => '{{/'.$token['path'].'}}'];
                $index++;

                continue;
            }
            $nodes[] = $this->parseTag($tokens, $index);
        }

        return $nodes;
    }

    /**
     * @param  list<array<string, string>>  $tokens
     * @return array<string, mixed>
     */
    private function parseTag(array $tokens, int &$index): array
    {
        $token = $tokens[$index];
        $index++;

        if ($token['sigil'] === '') {
            return ['type' => 'variable', 'path' => $token['path']];
        }

        $start = $index;
        $children = $this->parseNodes($tokens, $index, $token['path']);
        if ($index >= count($tokens) && ! $this->wasClosed($tokens, $start, $token['path'])) {
            // Unclosed section: render the opening tag literally, keep the body.
            return ['type' => 'unclosed', 'sigil' => $token['sigil'], 'path' => $token['path'], 'children' => $children];
        }

        return ['type' => $token['sigil'] === '#' ? 'section' : 'inverted', 'path' => $token['path'], 'children' => $children];
    }

    /**
     * @param  list<array<string, string>>  $tokens
     */
    private function wasClosed(array $tokens, int $start, string $path): bool
    {
        for ($i = $start; $i < count($tokens); $i++) {
            if ($tokens[$i]['type'] === 'tag' && $tokens[$i]['sigil'] === '/' && $tokens[$i]['path'] === $path) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     * @param  list<mixed>  $stack  innermost context last
     */
    private function renderNodes(array $nodes, array $stack): string
    {
        $output = '';
        foreach ($nodes as $node) {
            $output .= match ($node['type']) {
                'text' => $node['value'],
                'variable' => $this->escape($this->lookup($node['path'], $stack)),
                'section' => $this->renderSection($node, $stack),
                'inverted' => $this->isEmpty($this->lookup($node['path'], $stack)) ? $this->renderNodes($node['children'], $stack) : '',
                'unclosed' => '{{'.$node['sigil'].$node['path'].'}}'.$this->renderNodes($node['children'], $stack),
                default => '',
            };
        }

        return $output;
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  list<mixed>  $stack
     */
    private function renderSection(array $node, array $stack): string
    {
        $value = $this->lookup($node['path'], $stack);
        if ($this->isEmpty($value)) {
            return '';
        }
        if (! is_array($value)) {
            return $this->renderNodes($node['children'], $stack);
        }
        if (! array_is_list($value)) {
            return $this->renderNodes($node['children'], [...$stack, $value]);
        }

        $output = '';
        foreach ($value as $item) {
            $output .= $this->renderNodes($node['children'], [...$stack, $item]);
        }

        return $output;
    }

    /**
     * Walks the context stack outwards, so a section body can still read a
     * field carried by the template it sits in.
     *
     * @param  list<mixed>  $stack
     */
    private function lookup(string $path, array $stack): mixed
    {
        if ($path === '.') {
            return end($stack);
        }

        $segments = explode('.', $path);
        foreach (array_reverse($stack) as $context) {
            $value = $context;
            foreach ($segments as $segment) {
                if (! is_array($value) || ! array_key_exists($segment, $value)) {
                    $value = null;
                    break;
                }
                $value = $value[$segment];
            }
            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    private function isEmpty(mixed $value): bool
    {
        return $value === null || $value === false || $value === '' || $value === [];
    }

    private function escape(mixed $value): string
    {
        if ($value === null || is_bool($value) || is_array($value)) {
            return '';
        }

        return e((string) $value);
    }
}
