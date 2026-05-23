<?php

namespace Tests\Feature\Admin\Helpdesk;

use Tests\TestCase;

class TicketDownloadRealpathTest extends TestCase
{
    public function test_download_validates_path_with_realpath(): void
    {
        $reflection = new \ReflectionMethod(\App\Http\Controllers\Admin\Helpdesk\Support\TicketController::class, 'download');
        $file = file($reflection->getFileName());
        $body = implode('', array_slice($file, $reflection->getStartLine() - 1, $reflection->getEndLine() - $reflection->getStartLine() + 1));

        $this->assertStringContainsString(
            'realpath(',
            $body,
            'ticket download must compare resolved paths so a symlink stored as $attachment->path cannot escape storage/app/helpdesk/attachments'
        );
        $this->assertStringContainsString(
            'is_link(',
            $body,
            'ticket download must explicitly refuse symlinks even after the realpath comparison'
        );
    }
}
