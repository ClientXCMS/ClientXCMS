<?php

namespace Tests\Feature\Admin\Shared;

use Tests\TestCase;

class TextareaXssTest extends TestCase
{
    public function test_textarea_partial_escapes_inverifiedvalue(): void
    {
        $src = file_get_contents(resource_path('views/admin/shared/textarea.blade.php'));

        $this->assertStringNotContainsString(
            '{!! $Inverifiedvalue !!}',
            $src,
            'textarea must escape $Inverifiedvalue: a stored payload like </textarea><script>... breaks out of the textarea and runs in the admin context'
        );
        $this->assertStringContainsString(
            '{{ $Inverifiedvalue }}',
            $src,
            'Use Blade escaped output for textarea content'
        );
    }
}
