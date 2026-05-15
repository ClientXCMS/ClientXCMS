<?php

namespace Tests\Feature\Admin\Shared;

use Tests\TestCase;

class EditorXssTest extends TestCase
{
    public function test_editor_partial_does_not_render_raw_html_directly(): void
    {
        $src = file_get_contents(resource_path('views/admin/shared/editor.blade.php'));

        $this->assertStringNotContainsString(
            '{!! $value ?? old($name) !!}',
            $src,
            'editor must not echo $value as raw HTML inside <div id="editor-...">: a payload like <img src=x onerror=alert(1)> stored in DB would fire before Quill takes over'
        );
        $this->assertStringNotContainsString(
            '{!! $Inverifiedvalue !!}',
            $src,
            'editor must not echo $Inverifiedvalue as raw inside the hidden textarea (DOM-break via </textarea>)'
        );
    }

    public function test_editor_js_uses_dangerously_paste_html(): void
    {
        $src = file_get_contents(resource_path('global/js/editor.js'));

        $this->assertStringContainsString(
            'dangerouslyPasteHTML',
            $src,
            'editor.js must hand the saved content to quill.clipboard.dangerouslyPasteHTML so Quill performs its own sanitization instead of the browser parsing the raw HTML'
        );
    }
}
