<?php

namespace Tests\Feature\Admin\Security;

use Tests\TestCase;

class HistoryDashboardXssTest extends TestCase
{
    public function test_log_content_is_escaped_before_rendering(): void
    {
        $src = file_get_contents(resource_path('views/admin/dashboard/history/index.blade.php'));
        $this->assertStringNotContainsString(
            '{!! nl2br($content) !!}',
            $src,
            'history dashboard rendered raw log content - if any code path logs user input (Log::warning("bad name " . $request->name)) the admin viewing the log gets XSS'
        );
        $this->assertStringContainsString(
            '{!! nl2br(e($content)) !!}',
            $src,
            'log content must be e()-escaped before nl2br'
        );
    }
}
