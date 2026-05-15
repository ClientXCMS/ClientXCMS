<?php

namespace Tests\Feature\Admin\Personalization;

use App\Models\Admin\EmailTemplate;
use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailTemplateControllerRbacTest extends TestCase
{
    use RefreshDatabase;

    private function template(): EmailTemplate
    {
        $this->seed(EmailTemplateSeeder::class);

        return EmailTemplate::first();
    }

    public function test_show_blocks_admin_without_permission(): void
    {
        $template = $this->template();
        $response = $this->performAdminAction(
            'GET',
            route('admin.personalization.email_templates.show', $template),
            [],
            ['admin.show_invoices']
        );
        $response->assertStatus(403);
    }

    public function test_show_allows_admin_with_permission(): void
    {
        $template = $this->template();
        $response = $this->performAdminAction(
            'GET',
            route('admin.personalization.email_templates.show', $template),
            [],
            ['admin.manage_personalization']
        );
        $this->assertNotEquals(403, $response->status());
    }
}
