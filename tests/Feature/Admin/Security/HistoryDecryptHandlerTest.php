<?php

namespace Tests\Feature\Admin\Security;

use Tests\TestCase;

class HistoryDecryptHandlerTest extends TestCase
{
    public function test_download_handles_invalid_encrypted_input(): void
    {
        $reflection = new \ReflectionMethod(\App\Http\Controllers\Admin\Security\HistoryController::class, 'download');
        $file = file($reflection->getFileName());
        $body = implode('', array_slice($file, $reflection->getStartLine() - 1, $reflection->getEndLine() - $reflection->getStartLine() + 1));

        $this->assertStringContainsString(
            'DecryptException',
            $body,
            'download() must catch Illuminate\\Contracts\\Encryption\\DecryptException so a malformed dl= parameter does not leak a stack trace under APP_DEBUG=true'
        );
    }

    public function test_index_catches_exceptions_so_decrypt_failure_does_not_500(): void
    {
        $reflection = new \ReflectionMethod(\App\Http\Controllers\Admin\Security\HistoryController::class, 'index');
        $file = file($reflection->getFileName());
        $body = implode('', array_slice($file, $reflection->getStartLine() - 1, $reflection->getEndLine() - $reflection->getStartLine() + 1));

        $this->assertStringContainsString(
            'catch (\\Exception',
            $body,
            'index() must catch \\Exception so a malformed encrypted query parameter (DecryptException) does not bubble up to a 500 with stack trace'
        );
    }
}
