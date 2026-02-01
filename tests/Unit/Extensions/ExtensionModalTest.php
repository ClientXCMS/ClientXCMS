<?php

namespace Tests\Unit\Extensions;

use PHPUnit\Framework\TestCase;

class ExtensionModalTest extends TestCase
{
    private static string $basePath;

    public static function setUpBeforeClass(): void
    {
        self::$basePath = realpath(__DIR__.'/../../../');
    }

    private function resourcePath(string $path): string
    {
        return self::$basePath.'/resources/'.$path;
    }

    public function test_modal_js_file_exists_and_uses_dom_api(): void
    {
        $modalPath = $this->resourcePath('global/js/extensions/modal.js');
        $this->assertFileExists($modalPath);

        $content = file_get_contents($modalPath);

        // Core modal functions must exist
        $this->assertStringContainsString('function openModal', $content);
        $this->assertStringContainsString('function closeModal', $content);
        $this->assertStringContainsString('function populateModal', $content);
        $this->assertStringContainsString('function enableFocusTrap', $content);
        $this->assertStringContainsString('function buildModalActions', $content);

        // Must use DOM API for content creation (XSS prevention)
        $this->assertStringContainsString('createElement', $content);
        $this->assertStringContainsString('textContent', $content);
        $this->assertStringContainsString('appendChild', $content);

        // Must validate URLs against javascript: protocol injection
        $this->assertStringContainsString('isSafeUrl', $content);

        // Must have focus trap with keyboard navigation
        $this->assertStringContainsString('FOCUSABLE_SELECTOR', $content);
        $this->assertStringContainsString("e.key === 'Escape'", $content);
        $this->assertStringContainsString("e.key !== 'Tab'", $content);

        // Must announce modal opening via aria-live region
        $this->assertStringContainsString('js-extension-announcer', $content);
        $this->assertStringContainsString('announce(', $content);
    }

    public function test_card_templates_have_required_modal_data_attributes(): void
    {
        $cardPath = $this->resourcePath('views/admin/settings/extensions/_card.blade.php');
        $compactPath = $this->resourcePath('views/admin/settings/extensions/_card-compact.blade.php');
        $themePath = $this->resourcePath('views/admin/settings/extensions/_card-theme.blade.php');

        $requiredAttributes = [
            'data-name',
            'data-description',
            'data-author',
            'data-author-avatar',
            'data-thumbnail',
            'data-version',
            'data-price',
            'data-rating',
            'data-review-count',
            'data-uuid',
            'data-type',
            'data-installed',
            'data-enabled',
            'data-activable',
            'data-has-update',
            'data-enable-url',
            'data-disable-url',
            'data-install-url',
            'data-update-url',
        ];

        foreach ([$cardPath, $compactPath, $themePath] as $path) {
            $content = file_get_contents($path);
            $filename = basename($path);
            foreach ($requiredAttributes as $attr) {
                $this->assertStringContainsString(
                    $attr,
                    $content,
                    "Template {$filename} must contain {$attr} for modal data population"
                );
            }
        }
    }

    public function test_card_templates_have_detail_buttons(): void
    {
        $templates = [
            $this->resourcePath('views/admin/settings/extensions/_card.blade.php'),
            $this->resourcePath('views/admin/settings/extensions/_card-compact.blade.php'),
            $this->resourcePath('views/admin/settings/extensions/_card-theme.blade.php'),
        ];

        foreach ($templates as $path) {
            $content = file_get_contents($path);
            $filename = basename($path);
            $this->assertStringContainsString(
                'js-btn-details',
                $content,
                "Template {$filename} must have a .js-btn-details button for opening the modal"
            );
        }
    }

    public function test_index_template_has_modal_markup(): void
    {
        $indexPath = $this->resourcePath('views/admin/settings/extensions/index.blade.php');
        $content = file_get_contents($indexPath);

        // Modal container with accessibility attributes
        $this->assertStringContainsString('id="js-extension-modal"', $content);
        $this->assertStringContainsString('role="dialog"', $content);
        $this->assertStringContainsString('aria-modal="true"', $content);

        // Modal panel with JS hook class for backdrop click detection
        $this->assertStringContainsString('js-modal-panel', $content);

        // Close button
        $this->assertStringContainsString('js-modal-close', $content);

        // Content placeholders used by modal.js
        $this->assertStringContainsString('js-modal-title', $content);
        $this->assertStringContainsString('js-modal-thumbnail', $content);
        $this->assertStringContainsString('js-modal-description', $content);
        $this->assertStringContainsString('js-modal-author', $content);
        $this->assertStringContainsString('js-modal-rating', $content);
        $this->assertStringContainsString('js-modal-version', $content);
        $this->assertStringContainsString('js-modal-actions', $content);

        // Aria-live announcer region for screen reader announcements
        $this->assertStringContainsString('js-extension-announcer', $content);

        // Script inclusion
        $this->assertStringContainsString('modal.js', $content);

        // Must NOT have inline onclick handler (removed for proper JS event delegation)
        $this->assertStringNotContainsString(
            'onclick="event.stopPropagation()"',
            $content,
            'Modal panel must not use inline onclick -- event delegation handles backdrop clicks in modal.js'
        );
    }
}
