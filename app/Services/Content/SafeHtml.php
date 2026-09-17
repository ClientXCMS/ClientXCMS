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

namespace App\Services\Content;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Cleans markup that is later rendered without escaping.
 *
 * The allow-list is the sanitizer component's own baseline, maintained by that
 * project. Nothing here enumerates what to fear: script elements, event
 * attributes and javascript: urls are simply absent from the list, so no pattern
 * has to recognise them and no one has to keep that pattern up to date.
 *
 * Cleans on write rather than on render: a visitor pays nothing, and what sits
 * in the database is already safe.
 */
class SafeHtml
{
    private HtmlSanitizer $sanitizer;

    public function __construct()
    {
        $this->sanitizer = new HtmlSanitizer(
            (new HtmlSanitizerConfig)
                ->allowSafeElements()
                ->allowLinkSchemes(['https', 'http', 'mailto', 'tel'])
                ->allowRelativeLinks()
                ->allowRelativeMedias(),
        );
    }

    public function sanitize(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return $html;
        }

        return $this->sanitizer->sanitize($html);
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  list<string>  $keys  the ones rendered without escaping
     * @return array<string, mixed>
     */
    public function sanitizeKeys(array $values, array $keys): array
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $values)) {
                $values[$key] = $this->sanitize($values[$key]);
            }
        }

        return $values;
    }
}
