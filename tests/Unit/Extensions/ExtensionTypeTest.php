<?php

namespace Tests\Unit\Extensions;

use App\Extensions\ExtensionType;
use InvalidArgumentException;
use Tests\TestCase;

class ExtensionTypeTest extends TestCase
{
    public function test_it_accepts_singular_and_plural_type_names(): void
    {
        $this->assertSame(ExtensionType::Module, ExtensionType::tryFromAny('module'));
        $this->assertSame(ExtensionType::Module, ExtensionType::tryFromAny('modules'));
        $this->assertSame(ExtensionType::EmailTemplate, ExtensionType::tryFromAny('email_template'));
        $this->assertSame(ExtensionType::EmailTemplate, ExtensionType::tryFromAny('email_templates'));
        $this->assertNull(ExtensionType::tryFromAny('gateway'));
        $this->assertNull(ExtensionType::tryFromAny(null));
    }

    public function test_it_exposes_every_installable_type(): void
    {
        $this->assertSame(
            ['module', 'addon', 'theme', 'email_template', 'invoice_template'],
            ExtensionType::singularValues()
        );
        $this->assertSame(
            ['modules', 'addons', 'themes', 'email_templates', 'invoice_templates'],
            ExtensionType::pluralValues()
        );
    }

    public function test_it_resolves_the_install_path_of_each_type(): void
    {
        $this->assertSame('modules/demo', ExtensionType::Module->path('demo'));
        $this->assertSame('addons/demo', ExtensionType::Addon->path('demo'));
        $this->assertSame('resources/themes/demo', ExtensionType::Theme->path('demo'));
        $this->assertSame(
            'resources/views/vendor/notifications/demo.blade.php',
            ExtensionType::EmailTemplate->path('demo')
        );
        $this->assertSame(base_path('modules/demo'), ExtensionType::Module->absolutePath('demo'));
    }

    public function test_only_types_with_a_dedicated_directory_are_safe_to_prune(): void
    {
        $this->assertTrue(ExtensionType::Module->ownsDirectory());
        $this->assertTrue(ExtensionType::Addon->ownsDirectory());
        $this->assertTrue(ExtensionType::Theme->ownsDirectory());
        $this->assertFalse(ExtensionType::EmailTemplate->ownsDirectory());
        $this->assertFalse(ExtensionType::InvoiceTemplate->ownsDirectory());
    }

    public function test_an_extension_only_owns_files_inside_its_own_directory(): void
    {
        $type = ExtensionType::Module;

        $this->assertTrue($type->owns('modules/demo/src/Service.php', 'demo'));
        $this->assertTrue($type->owns('modules/demo/module.json', 'demo'));

        $this->assertFalse($type->owns('app/Http/Middleware/Authenticate.php', 'demo'));
        $this->assertFalse($type->owns('database/migrations/9999_evil.php', 'demo'));
        $this->assertFalse($type->owns('.env', 'demo'));
        $this->assertFalse($type->owns('composer.json', 'demo'));
        $this->assertFalse($type->owns('public/health.php', 'demo'));
        $this->assertFalse($type->owns('vendor/autoload.php', 'demo'));
    }

    public function test_an_extension_cannot_reach_another_extension(): void
    {
        $this->assertFalse(ExtensionType::Module->owns('modules/other/src/X.php', 'demo'));
        $this->assertFalse(ExtensionType::Addon->owns('modules/demo/src/X.php', 'demo'));
        $this->assertFalse(ExtensionType::Theme->owns('resources/themes/other/theme.json', 'demo'));
    }

    public function test_a_sibling_directory_sharing_the_prefix_is_not_owned(): void
    {
        $this->assertFalse(ExtensionType::Module->owns('modules/demo-evil/src/X.php', 'demo'));
        $this->assertFalse(ExtensionType::Module->owns('modules/demo.bak/src/X.php', 'demo'));
    }

    public function test_a_template_owns_only_the_three_files_the_product_reads(): void
    {
        $type = ExtensionType::EmailTemplate;
        $folder = 'resources/views/vendor/notifications/';

        $this->assertTrue($type->owns($folder.'wave.blade.php', 'wave'));
        $this->assertTrue($type->owns($folder.'wave_config.blade.php', 'wave'));
        $this->assertTrue($type->owns($folder.'wave_config.php', 'wave'));

        $this->assertFalse($type->owns($folder.'other.blade.php', 'wave'));
        $this->assertFalse($type->owns($folder.'wave_dark.blade.php', 'wave'));
        $this->assertFalse($type->owns($folder.'wave.blade.php.bak', 'wave'));
    }

    public function test_a_backslash_path_cannot_bypass_confinement(): void
    {
        $this->assertFalse(ExtensionType::Module->owns('modules\\other\\x.php', 'demo'));
        $this->assertTrue(ExtensionType::Module->owns('modules\\demo\\x.php', 'demo'));
    }

    public function test_it_refuses_an_identifier_that_could_escape_its_directory(): void
    {
        foreach (['../evil', 'demo/../..', 'demo/x', '', '.', 'de mo'] as $uuid) {
            try {
                ExtensionType::assertValidUuid($uuid);
                $this->fail("Identifier [{$uuid}] should have been refused");
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
