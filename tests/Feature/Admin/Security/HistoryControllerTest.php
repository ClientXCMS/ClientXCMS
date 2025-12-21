<?php

namespace Tests\Feature\Admin\Security;

use App\Services\Core\LogsReaderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HistoryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
    }

    public function test_index_view_renders_successfully()
    {
        $this->mock(LogsReaderService::class, function ($mock) {
            $mock->shouldReceive('getFolders')->andReturn([]);
            $mock->shouldReceive('getFolderName')->andReturn(null);
            $mock->shouldReceive('getFiles')->with(true)->andReturn([]);
            $mock->shouldReceive('getFileName')->andReturn(null);
            $mock->shouldReceive('foldersAndFiles')->andReturn([]);
            $mock->shouldReceive('getStoragePath')->andReturn('/fake/path');
            $mock->shouldReceive('get')->andReturn('log content');
        });

        $response = $this->performAdminAction('GET', route('admin.history.index'));
        $response->assertStatus(200);
    }

    public function test_index_with_invalid_encrypted_params_returns_error()
    {
        $response = $this->performAdminAction('GET', route('admin.history.index'), ['f' => 'invalid']);

        $response->assertSessionHas('error', 'The payload is invalid.');
    }
}
