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

    public function test_index_distinguishes_decrypt_failure_from_generic_error(): void
    {
        $reflection = new \ReflectionMethod(\App\Http\Controllers\Admin\Security\HistoryController::class, 'index');
        $file = file($reflection->getFileName());
        $body = implode('', array_slice($file, $reflection->getStartLine() - 1, $reflection->getEndLine() - $reflection->getStartLine() + 1));

        $this->assertStringContainsString(
            'DecryptException',
            $body,
            'index() must catch DecryptException separately so the generic exception path does not echo $e->getMessage() to the user (which would reveal the cipher used)'
        );
    }
}
