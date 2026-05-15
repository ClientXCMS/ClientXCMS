<?php

namespace Tests\Feature\Extensions;

use App\Extensions\UpdaterManager;
use Tests\TestCase;
use ZipArchive;

class UpdaterManagerHardeningTest extends TestCase
{
    private function makeZip(string $path, array $entries): void
    {
        if (file_exists($path)) {
            unlink($path);
        }
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        foreach ($entries as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();
    }

    public function test_zip_slip_with_parent_traversal_is_rejected(): void
    {
        $zip = sys_get_temp_dir().'/pentest-slip-1-'.uniqid().'.zip';
        $this->makeZip($zip, ['my-mod/../../../etc/evil' => 'x']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Zip slip attempt/i');
        try {
            UpdaterManager::rejectZipSlip($zip);
        } finally {
            @unlink($zip);
        }
    }

    public function test_zip_slip_with_absolute_path_is_rejected(): void
    {
        $zip = sys_get_temp_dir().'/pentest-slip-2-'.uniqid().'.zip';
        $this->makeZip($zip, ['/etc/passwd' => 'x']);

        $this->expectException(\RuntimeException::class);
        try {
            UpdaterManager::rejectZipSlip($zip);
        } finally {
            @unlink($zip);
        }
    }

    public function test_legit_extension_zip_passes_slip_check(): void
    {
        $zip = sys_get_temp_dir().'/pentest-ok-'.uniqid().'.zip';
        $this->makeZip($zip, [
            'my-extension/modules/my-extension/module.json' => '{}',
            'my-extension/modules/my-extension/src/X.php' => '<?php',
        ]);

        UpdaterManager::rejectZipSlip($zip);
        $this->assertTrue(true, 'no exception means the legit archive passed');
        @unlink($zip);
    }

    public function test_extracted_tree_outside_extension_dirs_is_rejected(): void
    {
        $tmp = sys_get_temp_dir().'/pentest-tree-'.uniqid();
        mkdir($tmp.'/app/Http/Controllers', 0755, true);
        file_put_contents($tmp.'/app/Http/Controllers/Backdoor.php', '<?php');

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessageMatches('/outside the allowed extension directories/');
            UpdaterManager::assertExtractedTreeStaysInExtensionDirs($tmp);
        } finally {
            @unlink($tmp.'/app/Http/Controllers/Backdoor.php');
            @rmdir($tmp.'/app/Http/Controllers');
            @rmdir($tmp.'/app/Http');
            @rmdir($tmp.'/app');
            @rmdir($tmp);
        }
    }

    public function test_extracted_tree_under_modules_passes(): void
    {
        $tmp = sys_get_temp_dir().'/pentest-tree-ok-'.uniqid();
        mkdir($tmp.'/modules/my-mod/src', 0755, true);
        file_put_contents($tmp.'/modules/my-mod/composer.json', '{}');
        file_put_contents($tmp.'/modules/my-mod/src/X.php', '<?php');

        try {
            UpdaterManager::assertExtractedTreeStaysInExtensionDirs($tmp);
            $this->assertTrue(true);
        } finally {
            @unlink($tmp.'/modules/my-mod/composer.json');
            @unlink($tmp.'/modules/my-mod/src/X.php');
            @rmdir($tmp.'/modules/my-mod/src');
            @rmdir($tmp.'/modules/my-mod');
            @rmdir($tmp.'/modules');
            @rmdir($tmp);
        }
    }
}
