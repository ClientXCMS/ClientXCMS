<?php
/*
 * This file is part of the CLIENTXCMS project.
 * It is the property of the CLIENTXCMS association.
 *
 * Personal and non-commercial use of this source code is permitted.
 * However, any use in a project that generates profit (directly or indirectly),
 * or any reuse for commercial purposes, requires prior authorization from CLIENTXCMS.
 *
 * To request permission or for more information, please contact our support:
 * https://clientxcms.com/client/support
 *
 * Year: 2025
 */
namespace Tests\Feature\Admin\Personalization;

use App\Models\Admin\EmailTemplate;
use App\Services\Core\LocaleService;
use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailTemplateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_template_index()
    {
        $this->seed(EmailTemplateSeeder::class);
        $response = $this->performAdminAction('GET', route('admin.personalization.email_templates.index'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.personalization.email_templates.index');
    }

    public function test_email_template_index_without_permission()
    {
        $this->seed(EmailTemplateSeeder::class);
        $response = $this->performAdminAction('GET', route('admin.personalization.email_templates.index'), [], ['admin.manage_products']);
        $response->assertStatus(403);
    }

    public function test_email_template_show()
    {
        $this->seed(EmailTemplateSeeder::class);
        $emailTemplate = EmailTemplate::first();
        $response = $this->performAdminAction('GET', route('admin.personalization.email_templates.show', $emailTemplate));
        $response->assertStatus(200);
        $response->assertViewIs('admin.personalization.email_templates.show');
    }

    public function test_email_template_create()
    {
        $this->seed(EmailTemplateSeeder::class);
        $response = $this->performAdminAction('GET', route('admin.personalization.email_templates.create'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.personalization.email_templates.create');
    }

    public function test_email_template_store()
    {
        $data = [
            'name' => 'Test Template',
            'subject' => 'Test Subject',
            'content' => 'Test Content',
            'button_text' => 'Test Button',
            'hidden' => false,
            'locale' => 'fr_FR',
        ];

        $response = $this->performAdminAction('POST', route('admin.personalization.email_templates.store'), $data);
        $response->assertStatus(302);
        $this->assertDatabaseCount('email_templates', count(LocaleService::getLocalesNames()));
    }

    public function test_email_template_update()
    {
        $this->seed(EmailTemplateSeeder::class);
        $emailTemplate = EmailTemplate::first();
        $data = [
            'name' => 'Updated Template',
            'subject' => 'Updated Subject',
            'content' => 'Updated Content',
            'button_text' => 'Updated Button',
            'hidden' => true,
            'locale' => 'fr_FR',
        ];
        $response = $this->performAdminAction('PUT', route('admin.personalization.email_templates.update', $emailTemplate), $data);
        $response->assertStatus(302);
        $this->assertDatabaseHas('email_templates', $data);
    }

    public function test_email_template_delete()
    {
        $this->seed(EmailTemplateSeeder::class);
        $emailTemplate = EmailTemplate::first();
        $response = $this->performAdminAction('DELETE', route('admin.personalization.email_templates.destroy', $emailTemplate));
        $response->assertStatus(302);
        $this->assertDatabaseMissing('email_templates', ['id' => $emailTemplate->id]);
    }
}
