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

namespace App\Helpers;

use Illuminate\Support\Facades\App;
use RuntimeException;
use Str;

/**
 * @see https://github.com/Azuriom/Azuriom/blob/new-installation/app/Support/EnvEditor.php
 */
class EnvEditor
{
    /**
     * Edit values in the environment file
     * Based on https://github.com/imliam/laravel-env-set-command, under MIT license.
     */
    public static function updateEnv(array $values, ?string $path = null)
    {
        $envPath = $path ?? App::environmentFilePath();
        $content = file_get_contents($envPath);

        if ($content === false) {
            throw new RuntimeException('Unable to read .env file: ' . $envPath);
        }

        foreach ($values as $key => $value) {
            $oldValue = self::getOldValue($content, $key);

            if ($oldValue === null) {
                $content .= "\n{$key}=";
            }

            if (Str::contains($value, [' ', '#'])) {
                $value = '"' . $value . '"';
            }

            $content = str_replace("{$key}={$oldValue}", "{$key}={$value}", $content);
        }
        if (file_put_contents($envPath, $content) === false) {
            throw new RuntimeException('Unable to write .env file: ' . $envPath);
        }
        Artisan::call('config:clear');
    }

    public static function putEnv(array $values, ?string $path = null)
    {
        self::updateEnv($values, $path);
    }

    protected static function getOldValue(string $envContents, string $key)
    {
        preg_match("/^{$key}=[^\r\n]*/m", $envContents, $matches);
        if (count($matches)) {
            return substr($matches[0], strlen($key) + 1);
        }

        return null;
    }
}
