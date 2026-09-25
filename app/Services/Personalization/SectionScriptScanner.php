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

namespace App\Services\Personalization;

use DOMDocument;
use DOMElement;

/**
 * Lists the script-bearing markup a section carries, so an operator knows what
 * will stop working before an upgrade rather than after.
 *
 * Reporting only. It walks the parsed document rather than matching patterns,
 * but a determined author can still hide markup from a parser, so a clean report
 * is not a security statement. Enforcement belongs to the sanitizer.
 */
class SectionScriptScanner
{
    /**
     * @return list<string> human-readable findings, empty when nothing was found
     */
    public function scan(string $html): array
    {
        if (trim($html) === '') {
            return [];
        }

        $document = $this->parse($html);
        if ($document === null) {
            return ['unparseable markup'];
        }

        $findings = [];
        foreach ($document->getElementsByTagName('*') as $element) {
            $findings = array_merge($findings, $this->inspect($element));
        }

        return array_values(array_unique($findings));
    }

    private function parse(string $html): ?DOMDocument
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML(
            '<!DOCTYPE html><html><body>'.$html.'</body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $loaded ? $document : null;
    }

    /**
     * @return list<string>
     */
    private function inspect(DOMElement $element): array
    {
        $findings = [];
        if (strtolower($element->tagName) === 'script') {
            $findings[] = '<script> element';
        }

        foreach ($element->attributes as $attribute) {
            $name = strtolower($attribute->name);
            if (str_starts_with($name, 'on')) {
                $findings[] = sprintf('%s attribute on <%s>', $name, strtolower($element->tagName));
            }
            if (str_starts_with(strtolower(ltrim($attribute->value)), 'javascript:')) {
                $findings[] = sprintf('javascript: url in %s', $name);
            }
        }

        return $findings;
    }
}
