<?php

namespace Tests\Feature;

use Tests\TestCase;

class FilePermissionsTest extends TestCase
{
    public static function targetFiles(): array
    {
        return [
            ['app/Extensions/ExtensionManager.php'],
            ['app/Models/Personalization/Section.php'],
        ];
    }

    /**
     * @dataProvider targetFiles
     */
    public function test_no_world_writable_mkdir_in_source(string $relative): void
    {
        $src = file_get_contents(base_path($relative));
        $this->assertStringNotContainsString(
            ', 0777,',
            $src,
            "{$relative} must not create world-writable directories. On a shared volume / SaaS pod, 0777 lets a neighbour tenant overwrite the files."
        );
    }
}
