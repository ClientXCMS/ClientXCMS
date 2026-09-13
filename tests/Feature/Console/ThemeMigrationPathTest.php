<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Filesystem\Filesystem;
use Tests\TestCase;

class ThemeMigrationPathTest extends TestCase
{
    use RefreshDatabase;

    private const THEME = 'migrationprobe';

    private const TABLE = 'theme_migration_probe';

    private string $themePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->themePath = base_path('resources/themes/'.self::THEME);
        (new Filesystem)->mkdir($this->themePath.'/database/migrations');
        file_put_contents(
            $this->themePath.'/database/migrations/2026_01_01_000000_create_theme_migration_probe_table.php',
            <<<'PHP'
            <?php

            use Illuminate\Database\Migrations\Migration;
            use Illuminate\Database\Schema\Blueprint;
            use Illuminate\Support\Facades\Schema;

            return new class extends Migration
            {
                public function up(): void
                {
                    Schema::create('theme_migration_probe', function (Blueprint $table) {
                        $table->id();
                    });
                }

                public function down(): void
                {
                    Schema::dropIfExists('theme_migration_probe');
                }
            };
            PHP
        );
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists(self::TABLE);
        (new Filesystem)->remove($this->themePath);
        parent::tearDown();
    }

    /**
     * Themes live in resources/themes, so a path built from the folder basename
     * points at themes/<uuid> and migrate reports "Nothing to migrate" on a
     * directory that does not exist, while the admin screen says it worked.
     */
    public function test_a_theme_migration_actually_runs(): void
    {
        $this->assertFalse(Schema::hasTable(self::TABLE));

        $this->artisan('clientxcms:db-extension', ['--extension' => self::THEME, '--action' => 'migrate'])
            ->assertExitCode(0);

        $this->assertTrue(
            Schema::hasTable(self::TABLE),
            'the theme migration was never applied: the command looked in the wrong directory'
        );
    }

    /**
     * The guard used to check the list of enabled extensions, so naming one
     * failed on an install where none happened to be enabled.
     */
    public function test_naming_an_extension_does_not_depend_on_the_others_being_enabled(): void
    {
        $this->artisan('clientxcms:db-extension', ['--extension' => self::THEME, '--action' => 'migrate'])
            ->doesntExpectOutputToContain('No extensions found')
            ->assertExitCode(0);
    }

    public function test_it_says_so_when_the_named_extension_does_not_exist(): void
    {
        $this->artisan('clientxcms:db-extension', ['--extension' => 'nosuchextension', '--action' => 'migrate'])
            ->expectsOutputToContain('Extension not found: nosuchextension')
            ->assertExitCode(0);
    }
}
