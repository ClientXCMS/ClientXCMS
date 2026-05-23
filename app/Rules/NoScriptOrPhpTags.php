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

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

/**
 * Blocks the well-known XSS / template-injection payloads in a free-form
 * text input that may legitimately contain a small amount of HTML
 * (basic formatting tags). Designed as a defense-in-depth layer; the
 * primary defense remains contextual escaping at render time.
 *
 * Backwards compatible: the original FORBIDDEN_TAGS_CONTENT and
 * FORBIDDEN_TAGS_FILES constants stay so existing call sites and any
 * subclass keep compiling. The pass() method now applies a wider set
 * of substring + regex checks.
 */
class NoScriptOrPhpTags implements Rule
{
    /**
     * Kept for backwards compatibility - subset checked via stripos.
     */
    const FORBIDDEN_TAGS_CONTENT = ['<script>', '<?php', '</script>', '?>', '<=', '<?=', '<%=', '<%', '<%', '{{', '{%'];

    const FORBIDDEN_TAGS_FILES = [
        '<script>', '<?php', '</script>',
    ];

    /**
     * Substring blocklist applied case-insensitively against text content.
     * Covers script execution vectors and the most common active-content tags.
     */
    private const EXTRA_FORBIDDEN_SUBSTRINGS = [
        '<script',          // <script src=...>, <script type=...>
        '<svg',             // SVG can carry <script>, on* handlers, animate
        '<iframe',
        '<object',
        '<embed',
        '<applet',
        '<base',            // can rebase relative URLs to attacker
        '<form',            // phishing inside the page
        '<meta',            // http-equiv refresh redirect
        '<link',            // rel="stylesheet"/"import"
        '<style',           // expression(), @import
        '<frame',
        '<frameset',
        '<noscript',
        '<template',
        '<math',            // can wrap script in MathML
        'javascript:',
        'vbscript:',
        'data:text/html',
        'data:application/xhtml',
        'expression(',      // legacy IE CSS expression()
        'srcdoc=',          // iframe srcdoc carries a fresh document
    ];

    /**
     * Regex blocklist. Catches inline event handlers in any element.
     */
    private const FORBIDDEN_PATTERNS = [
        '/\\son[a-z]+\\s*=/i',  // ' onload=', ' onerror=', ' onclick=', ...
    ];

    public function passes($attribute, $value)
    {
        $type = 'content';
        if ($value instanceof \Illuminate\Http\UploadedFile) {
            if (! $value->isValid() || ! $value->isReadable()) {
                return false;
            }
            $content = file_get_contents($value->getRealPath());
            $type = 'file';
        } else {
            $content = $value;
        }
        if (! is_string($content)) {
            return false;
        }

        $primary = $type === 'file' ? self::FORBIDDEN_TAGS_FILES : self::FORBIDDEN_TAGS_CONTENT;
        foreach ($primary as $tag) {
            if (stripos($content, $tag) !== false) {
                return false;
            }
        }

        if ($type === 'content') {
            foreach (self::EXTRA_FORBIDDEN_SUBSTRINGS as $needle) {
                if (stripos($content, $needle) !== false) {
                    return false;
                }
            }
            foreach (self::FORBIDDEN_PATTERNS as $pattern) {
                if (preg_match($pattern, $content)) {
                    return false;
                }
            }
        }

        return true;
    }

    public function message()
    {
        return 'The :attribute contains forbidden script, php or active-content tags.';
    }
}
