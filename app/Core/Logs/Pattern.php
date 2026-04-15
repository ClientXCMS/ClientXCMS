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

namespace App\Core\Logs;

/**
 * @see https://github.com/rap2hpoutre/laravel-log-viewer/blob/master/src/Rap2hpoutre/LaravelLogViewer/Pattern.php
 */
class Pattern
{
    /**
     * @var array<string, string>
     */
    private $patterns = [
        'logs' => '/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}([\+-]\d{4})?\].*/',
        'current_log' => [
            '/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}([\+-]\d{4})?)\](?:.*?(\w+)\.|.*?)',
            ': (.*?)( in .*?:[0-9]+)?$/i',
        ],
        'files' => '/\{.*?\,.*?\}/i',
    ];

    /**
     * @return string[]
     */
    public function all()
    {
        return array_keys($this->patterns);
    }

    /**
     * @param  string  $pattern
     * @param  null|string  $position
     * @return string pattern
     */
    public function getPattern($pattern, $position = null)
    {
        if ($position !== null) {
            return $this->patterns[$pattern][$position];
        }

        return $this->patterns[$pattern];
    }
}
