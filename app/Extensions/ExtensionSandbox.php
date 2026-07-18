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

namespace App\Extensions;

use App\DTO\Core\Extensions\ExtensionDTO;
use Composer\Autoload\ClassLoader;
use Illuminate\Foundation\Application;

class ExtensionSandbox
{
    public const SAFE_BOOT_ENV = 'APP_SAFE_BOOT';

    public static function safeBootEnabled(): bool
    {
        return filter_var(env(self::SAFE_BOOT_ENV, false), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Run the supplied extension's autoload() under a try/catch.
     * Returns true if the autoload completed cleanly, false (and
     * disables the extension) otherwise.
     */
    public static function autoload(
        ExtensionInterface $manager,
        ExtensionDTO $extension,
        Application $application,
        ClassLoader $composer
    ): bool {
        if (self::safeBootEnabled()) {
            logger()->info('extensions.safe_boot.skip', [
                'uuid' => $extension->uuid,
            ]);
            return false;
        }

        try {
            $manager->autoload($extension, $application, $composer);
            // Successful boot — clear any previous failure flag.
            self::clearBootError($extension);
            return true;
        } catch (\Throwable $e) {
            self::markFailed($extension, $e);
            return false;
        }
    }

    /**
     * Persist the failure into extensions.json so the admin UI can
     * surface it and so the NEXT request doesn't try the same broken
     * extension again.
     */
    public static function markFailed(ExtensionDTO $extension, \Throwable $e): void
    {
        logger()->error('extensions.boot_failed', [
            'uuid' => $extension->uuid,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        try {
            $cache = ExtensionManager::readExtensionJson();
            // ExtensionDTO::type() returns the bucket name directly
            // ("modules", "addons", "themes"). Reuse it as-is.
            $bucket = $extension->type();
            $list = $cache[$bucket] ?? [];

            foreach ($list as &$row) {
                if (($row['uuid'] ?? null) !== $extension->uuid) {
                    continue;
                }
                $row['enabled'] = false;
                $row['boot_error'] = [
                    'message' => mb_substr($e->getMessage(), 0, 500),
                    'class' => get_class($e),
                    'occurred_at' => now()->toIso8601String(),
                ];
                break;
            }
            unset($row);

            $cache[$bucket] = $list;
            ExtensionManager::writeExtensionJson($cache);
        } catch (\Throwable $persistError) {
            logger()->warning('extensions.boot_failed.persist_failed', [
                'uuid' => $extension->uuid,
                'error' => $persistError->getMessage(),
            ]);
        }
    }

    private static function clearBootError(ExtensionDTO $extension): void
    {
        if (! isset($extension->api['boot_error'])) {
            return;
        }
        try {
            $cache = ExtensionManager::readExtensionJson();
            // ExtensionDTO::type() returns the bucket name directly
            // ("modules", "addons", "themes"). Reuse it as-is.
            $bucket = $extension->type();
            foreach ($cache[$bucket] ?? [] as $idx => $row) {
                if (($row['uuid'] ?? null) === $extension->uuid && isset($row['boot_error'])) {
                    unset($cache[$bucket][$idx]['boot_error']);
                }
            }
            ExtensionManager::writeExtensionJson($cache);
        } catch (\Throwable $e) {
            // Non-blocking; the next boot will retry to clear it.
        }
    }
}
