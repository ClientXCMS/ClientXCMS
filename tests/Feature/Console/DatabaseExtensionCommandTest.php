<?php

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DatabaseExtensionCommandTest extends TestCase
{
    public function test_db_extension_rejects_path_traversal_dotdot(): void
    {
        Artisan::call('clientxcms:db-extension', ['--extension' => '../../tmp/evil']);
        $this->assertStringContainsString('Invalid extension identifier', Artisan::output());
    }

    public function test_db_extension_rejects_slash_in_identifier(): void
    {
        Artisan::call('clientxcms:db-extension', ['--extension' => 'evil/path']);
        $this->assertStringContainsString('Invalid extension identifier', Artisan::output());
    }

    public function test_db_extension_rejects_special_chars(): void
    {
        Artisan::call('clientxcms:db-extension', ['--extension' => 'evil; rm -rf /']);
        $this->assertStringContainsString('Invalid extension identifier', Artisan::output());
    }

    public function test_db_extension_accepts_valid_identifier(): void
    {
        Artisan::call('clientxcms:db-extension', ['--extension' => 'valid-name_123']);
        $this->assertStringNotContainsString('Invalid extension identifier', Artisan::output());
    }

    public function test_db_extension_rejects_when_no_option(): void
    {
        Artisan::call('clientxcms:db-extension');
        $this->assertStringContainsString('No extension specified', Artisan::output());
    }
}
