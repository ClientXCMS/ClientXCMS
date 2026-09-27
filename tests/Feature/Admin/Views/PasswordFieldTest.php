<?php

namespace Tests\Feature\Admin\Views;

use Tests\TestCase;

class PasswordFieldTest extends TestCase
{
    public function test_invalid_field_is_flagged_and_linked_to_its_error_and_help(): void
    {
        $html = (string) $this->withViewErrors(['password' => 'Wrong value'])
            ->view('admin.shared.password', ['name' => 'password', 'label' => 'Password', 'help' => 'Help text']);

        $input = $this->inputTag($html);
        $this->assertStringContainsString('aria-invalid="true"', $input);
        $this->assertMatchesRegularExpression('/aria-describedby="password-error password-help"/', $input);
        $this->assertMatchesRegularExpression('/id="password-error"[^>]*>\s*Wrong value/', $html);
        $this->assertMatchesRegularExpression('/id="password-help"[^>]*>Help text/', $html);
        $this->assertStringContainsString('for="password"', $html);
        $this->assertStringContainsString('id="password"', $input);
        $this->assertMatchesRegularExpression('/<button[^>]*aria-controls="password"[^>]*aria-pressed="false"/', $html);
    }

    public function test_valid_field_only_points_to_its_help(): void
    {
        $html = (string) $this->view('admin.shared.password', ['name' => 'password', 'label' => 'Password', 'help' => 'Help text']);

        $input = $this->inputTag($html);
        $this->assertStringNotContainsString('aria-invalid', $input);
        $this->assertStringContainsString('aria-describedby="password-help"', $input);
        $this->assertStringNotContainsString('password-error', $html);
    }

    public function test_field_without_help_or_error_has_no_description(): void
    {
        $input = $this->inputTag((string) $this->view('admin.shared.password', ['name' => 'password', 'label' => 'Password']));

        $this->assertStringNotContainsString('aria-describedby', $input);
    }

    public function test_same_field_included_twice_never_duplicates_an_id(): void
    {
        $html = (string) $this->blade(
            "@include('admin.shared.password', ['name' => 'password', 'label' => 'A', 'help' => 'H'])@include('admin.shared.password', ['name' => 'password', 'label' => 'B', 'help' => 'H'])"
        );

        preg_match_all('/\sid="([^"]+)"/', $html, $matches);
        $this->assertNotEmpty($matches[1]);
        $this->assertSame($matches[1], array_values(array_unique($matches[1])));
        $this->assertStringContainsString('for="password-2"', $html);
        $this->assertStringContainsString('aria-describedby="password-2-help"', $html);
    }

    private function inputTag(string $html): string
    {
        $this->assertSame(1, preg_match('/<input[^>]*type="password"[^>]*>/', $html, $match));

        return $match[0];
    }
}
