#!/usr/bin/env php
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

/**
 * Permission Validation Script
 *
 * This script validates that all permissions used in the codebase
 * are defined in resources/permissions.json and that roles.json
 * only references valid permissions.
 *
 * Usage: php scripts/validate-permissions.php
 * Exit codes: 0 = success, 1 = validation errors found
 */

define('BASE_PATH', dirname(__DIR__));

// ANSI color codes
define('COLOR_RED', "\033[31m");
define('COLOR_GREEN', "\033[32m");
define('COLOR_YELLOW', "\033[33m");
define('COLOR_BLUE', "\033[34m");
define('COLOR_RESET', "\033[0m");

class PermissionValidator
{
    private array $definedPermissions = [];
    private array $errors = [];
    private array $warnings = [];
    private int $filesScanned = 0;
    private int $permissionUsages = 0;

    public function __construct()
    {
        $this->loadDefinedPermissions();
    }

    private function loadDefinedPermissions(): void
    {
        $permissionsFile = BASE_PATH . '/resources/permissions.json';

        if (!file_exists($permissionsFile)) {
            $this->error("permissions.json not found at: {$permissionsFile}");
            exit(1);
        }

        $permissions = json_decode(file_get_contents($permissionsFile), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error("Invalid JSON in permissions.json: " . json_last_error_msg());
            exit(1);
        }

        foreach ($permissions as $permission) {
            if (isset($permission['name'])) {
                $this->definedPermissions[] = $permission['name'];
            }
        }

        $this->info("Loaded " . count($this->definedPermissions) . " defined permissions");
    }

    public function validate(): bool
    {
        $this->info("\n=== Starting Permission Validation ===\n");

        // Validate roles.json
        $this->validateRolesJson();

        // Scan PHP files for permission usage
        $this->scanPhpFiles();

        // Scan Blade files for permission usage
        $this->scanBladeFiles();

        // Report results
        $this->reportResults();

        return empty($this->errors);
    }

    private function validateRolesJson(): void
    {
        $this->info("Validating roles.json...");

        $rolesFile = BASE_PATH . '/resources/roles.json';

        if (!file_exists($rolesFile)) {
            $this->warning("roles.json not found");
            return;
        }

        $roles = json_decode(file_get_contents($rolesFile), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error("Invalid JSON in roles.json: " . json_last_error_msg());
            return;
        }

        foreach ($roles as $role) {
            $roleName = $role['name'] ?? 'Unknown';

            if (isset($role['permissions']) && is_array($role['permissions'])) {
                foreach ($role['permissions'] as $permission) {
                    if (!in_array($permission, $this->definedPermissions)) {
                        $this->error("Invalid permission '{$permission}' in role '{$roleName}' (roles.json)");
                    }
                }
            }
        }
    }

    private function scanPhpFiles(): void
    {
        $this->info("Scanning PHP files...");

        $directories = [
            BASE_PATH . '/app/Http/Controllers',
            BASE_PATH . '/app/Providers',
            BASE_PATH . '/app/DTO',
        ];

        foreach ($directories as $directory) {
            if (is_dir($directory)) {
                $this->scanDirectory($directory, '*.php', [$this, 'scanPhpFile']);
            }
        }
    }

    private function scanBladeFiles(): void
    {
        $this->info("Scanning Blade files...");

        $directory = BASE_PATH . '/resources/views/admin';

        if (is_dir($directory)) {
            $this->scanDirectory($directory, '*.blade.php', [$this, 'scanBladeFile']);
        }
    }

    private function scanDirectory(string $directory, string $pattern, callable $callback): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && fnmatch($pattern, $file->getFilename())) {
                $this->filesScanned++;
                call_user_func($callback, $file->getPathname());
            }
        }
    }

    private function scanPhpFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        $relativePath = str_replace(BASE_PATH . '/', '', $filePath);

        // Pattern 1: staff_has_permission('admin.xxx')
        if (preg_match_all("/staff_has_permission\s*\(\s*['\"]([^'\"]+)['\"]\s*\)/", $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[1] as $match) {
                $permission = $match[0];
                $position = $match[1];
                $lineNumber = substr_count(substr($content, 0, $position), "\n") + 1;

                $this->permissionUsages++;
                $this->validatePermissionUsage($permission, $relativePath, $lineNumber);
            }
        }

        // Pattern 2: staff_aborts_permission('admin.xxx')
        if (preg_match_all("/staff_aborts_permission\s*\(\s*['\"]([^'\"]+)['\"]\s*\)/", $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[1] as $match) {
                $permission = $match[0];
                $position = $match[1];
                $lineNumber = substr_count(substr($content, 0, $position), "\n") + 1;

                $this->permissionUsages++;
                $this->validatePermissionUsage($permission, $relativePath, $lineNumber);
            }
        }

        // Pattern 3: managedPermission = 'admin.xxx'
        if (preg_match_all("/managedPermission\s*=\s*['\"]([^'\"]+)['\"]/", $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[1] as $match) {
                $permission = $match[0];
                $position = $match[1];
                $lineNumber = substr_count(substr($content, 0, $position), "\n") + 1;

                $this->permissionUsages++;
                $this->validatePermissionUsage($permission, $relativePath, $lineNumber);
            }
        }

        // Pattern 4: addCardItem(..., 'admin.xxx')
        if (preg_match_all("/addCardItem\s*\([^)]*,\s*['\"]([^'\"]+)['\"]\s*\)/", $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[1] as $match) {
                $permission = $match[0];

                // Only validate if it looks like a permission (starts with 'admin.')
                if (strpos($permission, 'admin.') === 0) {
                    $position = $match[1];
                    $lineNumber = substr_count(substr($content, 0, $position), "\n") + 1;

                    $this->permissionUsages++;
                    $this->validatePermissionUsage($permission, $relativePath, $lineNumber);
                }
            }
        }

        // Pattern 5: AdminMenuItem(..., 'admin.xxx')
        if (preg_match_all("/new\s+AdminMenuItem\s*\([^)]*,\s*['\"]([^'\"]+)['\"]\s*\)/", $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[1] as $match) {
                $permission = $match[0];

                if (strpos($permission, 'admin.') === 0) {
                    $position = $match[1];
                    $lineNumber = substr_count(substr($content, 0, $position), "\n") + 1;

                    $this->permissionUsages++;
                    $this->validatePermissionUsage($permission, $relativePath, $lineNumber);
                }
            }
        }
    }

    private function scanBladeFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        $relativePath = str_replace(BASE_PATH . '/', '', $filePath);

        // Pattern: @if(staff_has_permission('admin.xxx'))
        if (preg_match_all("/@if\s*\(\s*staff_has_permission\s*\(\s*['\"]([^'\"]+)['\"]\s*\)/", $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[1] as $match) {
                $permission = $match[0];
                $position = $match[1];
                $lineNumber = substr_count(substr($content, 0, $position), "\n") + 1;

                $this->permissionUsages++;
                $this->validatePermissionUsage($permission, $relativePath, $lineNumber);
            }
        }

        // Pattern: staff_has_permission('admin.xxx')
        if (preg_match_all("/staff_has_permission\s*\(\s*['\"]([^'\"]+)['\"]\s*\)/", $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[1] as $match) {
                $permission = $match[0];
                $position = $match[1];
                $lineNumber = substr_count(substr($content, 0, $position), "\n") + 1;

                $this->permissionUsages++;
                $this->validatePermissionUsage($permission, $relativePath, $lineNumber);
            }
        }
    }

    private function validatePermissionUsage(string $permission, string $file, int $line): void
    {
        // Skip Permission constants (they start with Permission::)
        if (strpos($permission, 'Permission::') !== false) {
            return;
        }

        // Skip if permission doesn't follow admin.* pattern
        if (strpos($permission, 'admin.') !== 0) {
            return;
        }

        if (!in_array($permission, $this->definedPermissions)) {
            $this->error("Invalid permission '{$permission}' used in {$file}:{$line}");
        }
    }

    private function reportResults(): void
    {
        echo "\n" . str_repeat('=', 60) . "\n";
        echo COLOR_BLUE . "Validation Report" . COLOR_RESET . "\n";
        echo str_repeat('=', 60) . "\n\n";

        echo "Files scanned: {$this->filesScanned}\n";
        echo "Permission usages found: {$this->permissionUsages}\n";
        echo "Defined permissions: " . count($this->definedPermissions) . "\n\n";

        if (!empty($this->warnings)) {
            echo COLOR_YELLOW . "⚠ Warnings (" . count($this->warnings) . "):" . COLOR_RESET . "\n";
            foreach ($this->warnings as $warning) {
                echo "  - {$warning}\n";
            }
            echo "\n";
        }

        if (!empty($this->errors)) {
            echo COLOR_RED . "✗ Errors (" . count($this->errors) . "):" . COLOR_RESET . "\n";
            foreach ($this->errors as $error) {
                echo "  - {$error}\n";
            }
            echo "\n";
            echo COLOR_RED . "Validation FAILED" . COLOR_RESET . "\n";
        } else {
            echo COLOR_GREEN . "✓ Validation PASSED - All permissions are valid!" . COLOR_RESET . "\n";
        }

        echo str_repeat('=', 60) . "\n";
    }

    private function error(string $message): void
    {
        $this->errors[] = $message;
    }

    private function warning(string $message): void
    {
        $this->warnings[] = $message;
    }

    private function info(string $message): void
    {
        echo COLOR_BLUE . $message . COLOR_RESET . "\n";
    }
}

// Run validation
$validator = new PermissionValidator();
$success = $validator->validate();

exit($success ? 0 : 1);
