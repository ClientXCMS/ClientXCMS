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

class NoScriptOrPhpTags implements Rule
{

    const FORBIDDEN_TAGS_CONTENT = ['<script>', '<?php', '</script>', '?>', '<=', '<?=', '<%=', '<%', '<%', '{{', '{%'];
    const FORBIDDEN_TAGS_FILES = [
        '<script>', '<?php', '</script>',
    ];
    public function passes($attribute, $value)
    {
        $type = 'content';
        if ($value instanceof \Illuminate\Http\UploadedFile) {
            if (!$value->isValid() || !$value->isReadable()) {
                return false;
            }
            $content = file_get_contents($value->getRealPath());
            $type = 'file';
        } else {
            $content = $value;
        }
        if (!is_string($content)) {
            return false;
        }
        foreach ($type == 'file' ? self::FORBIDDEN_TAGS_FILES : self::FORBIDDEN_TAGS_CONTENT as $tag) {
            if (stripos($content, $tag) !== false) {
                return false;
            }
        }

        return true;
    }

    public function message()
    {
        return 'The :attribute contains forbidden script or PHP tags.';
    }
}
