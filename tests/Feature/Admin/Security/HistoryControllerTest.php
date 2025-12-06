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

    //    public function test_download_returns_download_response()
    //    {
    //        Storage::fake('local');
    //        $filePath = storage_path('logs/test.log');
    //        file_put_contents($filePath, 'log');
    //
    //        $encrypted = Crypt::encrypt('test.log');
    //
    //        $this->mock(LogsReaderService::class, function ($mock) use ($filePath) {
    //            $mock->shouldReceive('pathToLogFile')->andReturn($filePath);
    //        });
    //        $response = $this->performAdminAction("GET", route('admin.history.download', $encrypted));
    //
    //    }

    //    public function test_clear_clears_log_file()
    //    {
    //        $filePath = storage_path('logs/test.log');
    //        File::put($filePath, 'test log content');
    //
    //        $encrypted = Crypt::encrypt('test.log');
    //
    //        $this->mock(LogsReaderService::class, function ($mock) use ($filePath) {
    //            $mock->shouldReceive('pathToLogFile')->andReturn($filePath);
    //        });
    //        $response = $this->performAdminAction("GET", route('admin.history.clear', $encrypted));
    //
    //        $response->assertRedirect();
    //        $response->assertSessionHas('success', 'File has been cleared');
    //        $this->assertEquals('', file_get_contents($filePath));
    //    }

    // public function test_delete_deletes_file_successfully()
    // {
    //     $filePath = storage_path('logs/to_delete.log');
    //     File::put($filePath, 'content');

    //     $this->mock(LogsReaderService::class, function ($mock) use ($filePath) {
    //         $mock->shouldReceive('pathToLogFile')->andReturn($filePath);
    //     });
    //     $response = $this->performAdminAction('get', route('admin.history.delete'), [
    //         'del' => 'to_delete.log',
    //     ]);

    //     $response->assertRedirect();
    //     $response->assertSessionHas('success', 'File has been deleted');
    //     $this->assertFalse(file_exists($filePath));
    // }
    //
    //    public function test_delete_all_removes_all_log_files()
    //    {
    //        $folder = storage_path('logs/folder');
    //        File::ensureDirectoryExists($folder);
    //
    //        $file1 = $folder . '/1.log';
    //        $file2 = $folder . '/2.log';
    //        File::put($file1, 'log1');
    //        File::put($file2, 'log2');
    //
    //        $this->mock(LogsReaderService::class, function ($mock) use ($file1, $file2) {
    //            $mock->shouldReceive('getFolderName')->andReturn('folder');
    //            $mock->shouldReceive('setFolder');
    //            $mock->shouldReceive('getFolderFiles')->with(true)->andReturn([$file1, $file2]);
    //        });
    //
    //        $encrypted = Crypt::encrypt('folder');
    //        $response = $this->performAdminAction("get", route('admin.history.deleteall', $encrypted), [
    //            'f' => $encrypted,
    //        ]);
    //
    //        $response->assertRedirect();
    //        $response->assertSessionHas('success', 'All files have been deleted');
    //        $this->assertFalse(file_exists($file1));
    //        $this->assertFalse(file_exists($file2));
    //    }
}
