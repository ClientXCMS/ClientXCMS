<?php

/*
 * This file is part of the CLIENTXCMS project.
 * Year: 2026 — v2.16 release.
 */

namespace App\Extensions;

use App\DTO\Core\Extensions\ExtensionDTO;
use Composer\Autoload\ClassLoader;
use Illuminate\Foundation\Application;

/**
 * v2.16 — Tiny try/catch wrapper around extension boot so a broken
 * extension can never bring the whole app down.
 *
 * Operators occasionally upload an extension whose service provider
 * throws on register/boot — missing dependency, fatal in a constructor,
 * incompatible PHP / Laravel version. Before v2.16 that crash bubbled
 * up to the kernel and the customer area went 500. With this
 * sandbox:
 *
 *   1. The faulty extension is automatically marked disabled in
 *      `bootstrap/cache/extensions.json` so the NEXT request boots
 *      fine without the broken code.
 *   2. The error is captured and surfaced in `/admin/extensions` via
 *      the `boot_error` metadata so the operator knows what to fix.
 *   3. The current request keeps going — the rest of the extensions
 *      and the core app boot normally.
 *
 * A "safe boot" mode is also available via the APP_SAFE_BOOT env var:
 * when set to "true" the sandbox skips every extension during autoload
 * (and ExtensionManager::readExtensionJson() honours it too). Useful
 * when the operator needs to log in to fix a broken extension that's
 * preventing /admin/extensions itself from rendering.
 */
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
