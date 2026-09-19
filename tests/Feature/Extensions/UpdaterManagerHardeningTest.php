<?php

namespace Tests\Feature\Extensions;

use App\Extensions\ExtensionType;
use App\Extensions\UpdaterManager;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Filesystem\Filesystem;
use Tests\TestCase;
use ZipArchive;

class UpdaterManagerHardeningTest extends TestCase
{
    private string $sandbox;

    private string $projectRoot;

    private string $extractDir;

    private string $previousBasePath;

    private string $previousCwd;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sandbox = sys_get_temp_dir().'/ctx-updater-'.bin2hex(random_bytes(6));
        $this->projectRoot = $this->sandbox.'/project';
        $this->extractDir = $this->sandbox.'/extract';
        (new Filesystem)->mkdir([$this->projectRoot, $this->extractDir]);
        $this->previousBasePath = base_path();
        $this->previousCwd = (string) getcwd();
        $this->app->setBasePath($this->projectRoot);
    }

    protected function tearDown(): void
    {
        chdir($this->previousCwd);
        $this->app->setBasePath($this->previousBasePath);
        (new Filesystem)->remove($this->sandbox);
        parent::tearDown();
    }

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

    public function test_it_deletes_the_downloaded_archive(): void
    {
        $archive = $this->sandbox.'/package.zip';
        $this->makeZip($archive, ['package/modules/demo/module.json' => '{}']);

        (new UpdaterManager)->extract($archive, $this->extractDir);

        $this->assertFileDoesNotExist($archive, 'the downloaded archive must be cleaned up');
        $this->assertDirectoryDoesNotExist($this->extractDir, 'the extract directory must be cleaned up');
    }

    public function test_it_keeps_the_project_license_when_updating(): void
    {
        $archive = $this->sandbox.'/package.zip';
        $this->makeZip($archive, [
            'package/modules/demo/module.json' => '{}',
            'package/LICENSE' => 'extension licence',
        ]);
        file_put_contents($this->projectRoot.'/LICENSE', 'product licence');
        chdir($this->projectRoot);

        (new UpdaterManager)->extract($archive, $this->extractDir);

        $this->assertFileExists($this->projectRoot.'/LICENSE', 'updating must not delete the project LICENSE');
    }

    public function test_it_refuses_an_archive_without_a_root_directory(): void
    {
        $archive = $this->sandbox.'/flat.zip';
        $this->makeZip($archive, ['loose.txt' => 'x']);

        $this->expectException(\RuntimeException::class);
        (new UpdaterManager)->extract($archive, $this->extractDir);
    }

    public function test_an_extension_archive_only_writes_inside_its_own_directory(): void
    {
        $archive = $this->sandbox.'/payload.zip';
        $this->makeZip($archive, [
            'package/modules/demo/module.json' => '{"uuid":"demo"}',
            'package/modules/demo/src/Service.php' => '<?php // legit',
            'package/app/Http/Middleware/Authenticate.php' => '<?php // backdoor',
            'package/database/migrations/9999_99_99_999999_evil.php' => '<?php // backdoor',
            'package/public/health.php' => '<?php // backdoor',
            'package/.env' => 'APP_KEY=stolen',
            'package/composer.json' => '{"require":{}}',
        ]);

        (new UpdaterManager)->extractExtension($archive, $this->extractDir, ExtensionType::Module, 'demo');

        $this->assertFileExists($this->projectRoot.'/modules/demo/module.json');
        $this->assertFileExists($this->projectRoot.'/modules/demo/src/Service.php');

        $this->assertFileDoesNotExist($this->projectRoot.'/app/Http/Middleware/Authenticate.php');
        $this->assertFileDoesNotExist($this->projectRoot.'/database/migrations/9999_99_99_999999_evil.php');
        $this->assertFileDoesNotExist($this->projectRoot.'/public/health.php');
        $this->assertFileDoesNotExist($this->projectRoot.'/.env');
        $this->assertFileDoesNotExist($this->projectRoot.'/composer.json');
    }

    public function test_it_reports_the_files_it_dropped_from_the_archive(): void
    {
        Log::spy();
        $archive = $this->sandbox.'/mispackaged.zip';
        $this->makeZip($archive, [
            'package/modules/demo/module.json' => '{"uuid":"demo"}',
            'package/public/assets/demo.css' => 'body{}',
            'package/app/Http/Kernel.php' => '<?php // backdoor',
        ]);

        (new UpdaterManager)->extractExtension($archive, $this->extractDir, ExtensionType::Module, 'demo');

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return $message === 'extensions.update.files_outside_extension_dropped'
                    && $context['uuid'] === 'demo'
                    && $context['dropped'] === 2
                    && in_array('public/assets/demo.css', $context['sample'], true)
                    && in_array('app/Http/Kernel.php', $context['sample'], true);
            });
    }

    public function test_it_stays_quiet_when_the_archive_only_carries_its_own_files(): void
    {
        Log::spy();
        $archive = $this->sandbox.'/clean.zip';
        $this->makeZip($archive, [
            'package/modules/demo/module.json' => '{"uuid":"demo"}',
            'package/README.md' => 'documentation',
        ]);

        (new UpdaterManager)->extractExtension($archive, $this->extractDir, ExtensionType::Module, 'demo');

        Log::shouldNotHaveReceived('warning');
    }

    public function test_an_extension_archive_cannot_touch_another_extension(): void
    {
        $archive = $this->sandbox.'/neighbour.zip';
        $this->makeZip($archive, [
            'package/modules/demo/module.json' => '{"uuid":"demo"}',
            'package/modules/victim/src/Service.php' => '<?php // hijacked',
        ]);
        (new Filesystem)->mkdir($this->projectRoot.'/modules/victim/src');
        file_put_contents($this->projectRoot.'/modules/victim/src/Service.php', '<?php // original');

        (new UpdaterManager)->extractExtension($archive, $this->extractDir, ExtensionType::Module, 'demo');

        $this->assertStringContainsString(
            'original',
            (string) file_get_contents($this->projectRoot.'/modules/victim/src/Service.php')
        );
    }

    public function test_an_extension_archive_that_carries_nothing_of_its_own_is_refused(): void
    {
        $archive = $this->sandbox.'/empty.zip';
        $this->makeZip($archive, ['package/app/Http/Kernel.php' => '<?php // backdoor']);

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessageMatches('/does not contain modules\/demo/');
            (new UpdaterManager)->extractExtension($archive, $this->extractDir, ExtensionType::Module, 'demo');
        } finally {
            $this->assertFileDoesNotExist($this->projectRoot.'/app/Http/Kernel.php');
        }
    }

    public function test_an_email_template_archive_only_writes_the_files_the_product_reads(): void
    {
        $archive = $this->sandbox.'/template.zip';
        $folder = 'resources/views/vendor/notifications';
        $this->makeZip($archive, [
            "package/{$folder}/wave.blade.php" => 'template',
            "package/{$folder}/wave_config.blade.php" => 'config',
            "package/{$folder}/other.blade.php" => 'someone else template',
            'package/app/Console/Kernel.php' => '<?php // backdoor',
        ]);

        (new UpdaterManager)->extractExtension($archive, $this->extractDir, ExtensionType::EmailTemplate, 'wave');

        $this->assertFileExists($this->projectRoot."/{$folder}/wave.blade.php");
        $this->assertFileExists($this->projectRoot."/{$folder}/wave_config.blade.php");
        $this->assertFileDoesNotExist($this->projectRoot."/{$folder}/other.blade.php");
        $this->assertFileDoesNotExist($this->projectRoot.'/app/Console/Kernel.php');
    }

    public function test_the_product_update_still_writes_outside_extension_directories(): void
    {
        $archive = $this->sandbox.'/core.zip';
        $this->makeZip($archive, [
            'package/app/Providers/AppServiceProvider.php' => '<?php // core',
            'package/config/app.php' => '<?php return [];',
        ]);

        (new UpdaterManager)->extract($archive, $this->extractDir);

        $this->assertFileExists($this->projectRoot.'/app/Providers/AppServiceProvider.php');
        $this->assertFileExists($this->projectRoot.'/config/app.php');
    }

    public function test_updating_removes_a_file_the_new_archive_no_longer_ships(): void
    {
        (new Filesystem)->mkdir($this->projectRoot.'/modules/demo/src');
        file_put_contents($this->projectRoot.'/modules/demo/module.json', '{"uuid":"demo","version":"1.0"}');
        file_put_contents($this->projectRoot.'/modules/demo/src/Removed.php', '<?php // renamed upstream');

        $archive = $this->sandbox.'/package.zip';
        $this->makeZip($archive, [
            'package/modules/demo/module.json' => '{"uuid":"demo","version":"1.1"}',
        ]);

        (new UpdaterManager)->extractExtension($archive, $this->extractDir, ExtensionType::Module, 'demo');

        $this->assertFileDoesNotExist(
            $this->projectRoot.'/modules/demo/src/Removed.php',
            'a file dropped from the new archive must not linger on disk'
        );
        $this->assertStringContainsString(
            '1.1',
            (string) file_get_contents($this->projectRoot.'/modules/demo/module.json')
        );
    }

    public function test_updating_never_touches_a_file_outside_the_extension_directory_while_pruning(): void
    {
        (new Filesystem)->mkdir($this->projectRoot.'/modules/demo');
        file_put_contents($this->projectRoot.'/modules/demo/module.json', '{"uuid":"demo"}');
        (new Filesystem)->mkdir($this->projectRoot.'/app/Http/Middleware');
        file_put_contents($this->projectRoot.'/app/Http/Middleware/Authenticate.php', '<?php // untouched');

        $archive = $this->sandbox.'/package.zip';
        $this->makeZip($archive, [
            'package/modules/demo/module.json' => '{"uuid":"demo","version":"2"}',
        ]);

        (new UpdaterManager)->extractExtension($archive, $this->extractDir, ExtensionType::Module, 'demo');

        $this->assertFileExists(
            $this->projectRoot.'/app/Http/Middleware/Authenticate.php',
            'pruning the extension directory must never reach outside it'
        );
    }

    public function test_updating_an_email_template_does_not_prune_the_shared_directory(): void
    {
        $folder = 'resources/views/vendor/notifications';
        (new Filesystem)->mkdir($this->projectRoot.'/'.$folder);
        file_put_contents($this->projectRoot."/{$folder}/other.blade.php", 'someone else template');

        $archive = $this->sandbox.'/template.zip';
        $this->makeZip($archive, [
            "package/{$folder}/wave.blade.php" => 'template',
        ]);

        (new UpdaterManager)->extractExtension($archive, $this->extractDir, ExtensionType::EmailTemplate, 'wave');

        $this->assertFileExists(
            $this->projectRoot."/{$folder}/other.blade.php",
            'templates share a directory: pruning must stay out of scope for this type'
        );
    }
}
