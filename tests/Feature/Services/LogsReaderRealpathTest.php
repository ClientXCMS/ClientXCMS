<?php

namespace Tests\Feature\Services;

use Tests\TestCase;

class LogsReaderRealpathTest extends TestCase
{
    public function test_path_to_log_file_uses_realpath_against_root(): void
    {
        $reflection = new \ReflectionMethod(\App\Services\Core\LogsReaderService::class, 'pathToLogFile');
        $file = file($reflection->getFileName());
        $body = implode('', array_slice($file, $reflection->getStartLine() - 1, $reflection->getEndLine() - $reflection->getStartLine() + 1));

        $this->assertStringContainsString(
            'realpath(',
            $body,
            'pathToLogFile must call realpath() so a symlink under storage/logs cannot escape the logs directory'
        );
        $this->assertStringContainsString(
            'str_starts_with(',
            $body,
            'pathToLogFile must check the resolved file stays under the resolved logs root'
        );
        $this->assertStringContainsString(
            'is_link(',
            $body,
            'pathToLogFile must explicitly reject symlinks (defense-in-depth on top of the realpath comparison)'
        );
    }
}
