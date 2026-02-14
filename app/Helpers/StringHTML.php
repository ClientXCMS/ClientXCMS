<?php

namespace App\Helpers;

class StringHTML
{
    public static function toHtml(string $string): string
    {
        return nl2br(htmlspecialchars($string));
    }

    public static function fromHtml(string $string): string
    {
        return htmlspecialchars_decode($string);
    }

    public static function htmlToPlainLines(string $input, string $a = '- '): string
    {
        $trimmed = trim($input);

        if ($trimmed === strip_tags($trimmed)) {
            return self::normalizeSpaces($trimmed);
        }

        $dom = new \DOMDocument;
        \libxml_use_internal_errors(true);

        $html =
            '<!DOCTYPE html><html><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><body>'.
            $trimmed.
            '</body></html>';

        $dom->loadHTML($html, \LIBXML_HTML_NOIMPLIED | \LIBXML_HTML_NODEFDTD);
        \libxml_clear_errors();

        $lines = [];

        $walker = function (\DOMNode $node, int $depth = 0) use (&$walker, &$lines) {
            $name = strtolower($node->nodeName);

            if ($name === 'li') {
                $text = self::nodeText($node);
                if ($text !== '') {
                    $prefix = str_repeat('  ', max(0, $depth - 1)).$a;
                    $lines[] = $prefix.$text;
                }

                return;
            }

            if ($name === 'p') {
                $text = self::nodeText($node);
                if ($text !== '') {
                    $lines[] = $text;
                }

                return;
            }

            if ($name === 'br') {
                $lines[] = '';

                return;
            }

            if ($name === 'ul' || $name === 'ol') {
                foreach ($node->childNodes as $child) {
                    $walker($child, $depth + 1);
                }

                return;
            }
            foreach ($node->childNodes as $child) {
                $walker($child, $depth);
            }
        };

        $body = $dom->getElementsByTagName('body')->item(0) ?: $dom;
        $walker($body, 0);
        $clean = [];
        $prevEmpty = true;
        foreach ($lines as $line) {
            $l = rtrim($line);
            if ($l === '') {
                if (! $prevEmpty) {
                    $clean[] = '';
                }
                $prevEmpty = true;
            } else {
                $clean[] = $l;
                $prevEmpty = false;
            }
        }
        while (! empty($clean) && end($clean) === '') {
            array_pop($clean);
        }

        return implode(PHP_EOL, $clean);
    }

    private static function nodeText(\DOMNode $node): string
    {
        $text = $node->textContent ?? '';

        return self::normalizeSpaces($text);
    }

    private static function normalizeSpaces(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim($text);
    }
}
