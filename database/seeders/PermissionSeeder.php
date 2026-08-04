<?php

namespace Database\Seeders;

use App\Models\Admin\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    private const MAX_MAPPING_FILE_SIZE = 1_048_576;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = $this->readPermissions(resource_path('permissions.json')) ?? [];
        $extensions = app('extension')->getAllExtensions(false, true);
        foreach ($extensions as $extension) {
            if (! in_array($extension->type(), ['modules', 'addons'])) {
                continue;
            }
            $path = $extension->extensionPath().'/permissions.json';
            if (! is_file($path)) {
                continue;
            }

            $extensionPermissions = $this->readPermissions($path);
            if ($extensionPermissions !== null) {
                $permissions = array_merge($permissions, $extensionPermissions);
            }
        }

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name']],
                ['label' => $permission['label'], 'group' => $permission['group'] ?? null],
            );
        }
    }

    private function readPermissions(string $path): ?array
    {
        if (! is_readable($path)) {
            logger()->warning('[PermissionSeeder] Unable to safely read permissions mapping.', compact('path'));

            return null;
        }

        $size = filesize($path);
        if ($size === false || $size > self::MAX_MAPPING_FILE_SIZE) {
            logger()->warning('[PermissionSeeder] Unable to safely read permissions mapping.', compact('path', 'size'));

            return null;
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            logger()->warning('[PermissionSeeder] Unable to read permissions mapping.', compact('path'));

            return null;
        }

        try {
            $permissions = json_decode($contents, true, 16, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            logger()->warning('[PermissionSeeder] Invalid permissions JSON skipped.', [
                'path' => $path,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }

        if (! is_array($permissions) || ! array_is_list($permissions)) {
            logger()->warning('[PermissionSeeder] Permissions mapping must be a JSON array.', compact('path'));

            return null;
        }

        return array_values(array_filter($permissions, function (mixed $permission, int $index) use ($path): bool {
            $valid = is_array($permission)
                && isset($permission['name'], $permission['label'])
                && is_string($permission['name'])
                && trim($permission['name']) !== ''
                && strlen($permission['name']) <= 255
                && is_string($permission['label'])
                && trim($permission['label']) !== ''
                && strlen($permission['label']) <= 255
                && (! array_key_exists('group', $permission) || $permission['group'] === null || (is_string($permission['group']) && strlen($permission['group']) <= 255));

            if (! $valid) {
                logger()->warning('[PermissionSeeder] Invalid permission mapping entry skipped.', compact('path', 'index'));
            }

            return $valid;
        }, ARRAY_FILTER_USE_BOTH));
    }
}
