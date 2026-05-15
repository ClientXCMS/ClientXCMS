<?php

namespace Tests\Feature\Admin\Personalization;

use App\Models\Admin\EmailTemplate;
use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
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
    }

    public function test_email_template_store_rejects_dangerous_function_calls()
    {
        $data = [
            'name' => 'Test Template',
            'subject' => 'Test Subject',
            'content' => "{{ system('id') }}",
            'button_text' => 'Test Button',
            'hidden' => false,
            'locale' => 'fr_FR',
        ];

        $response = $this->performAdminAction('POST', route('admin.personalization.email_templates.store'), $data);

        $response->assertStatus(302);
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('email_templates', ['name' => 'Test Template']);
    }

    public function test_email_template_update_rejects_dangerous_function_calls()
    {
        $this->seed(EmailTemplateSeeder::class);
        $emailTemplate = EmailTemplate::first();

        $data = [
            'name' => 'Updated Template',
            'subject' => 'Updated Subject',
            'content' => "{{ file_get_contents('.env') }}",
            'button_text' => 'Updated Button',
            'hidden' => true,
            'locale' => 'fr_FR',
        ];

        $response = $this->performAdminAction('PUT', route('admin.personalization.email_templates.update', $emailTemplate), $data);

        $response->assertStatus(302);
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('email_templates', ['id' => $emailTemplate->id, 'content' => "{{ file_get_contents('.env') }}"]);
    }

    public static function indirectInvocationPayloads(): array
    {
        return [
            'call_user_func' => ["{{ call_user_func('system', 'id') }}"],
            'array_map' => ["{{ array_map('system', ['id'])[0] }}"],
            'string_concat' => ["{{ ('sys' . 'tem')('id') }}"],
            'closure_callable' => ["{{ \\Closure::fromCallable('system')('id') }}"],
            'reflection' => ["{{ (new ReflectionFunction('system'))->invoke('id') }}"],
            'variable_function' => ["{{ (\$x = 'system')('id') }}"],
        ];
    }

    #[DataProvider('indirectInvocationPayloads')]
    public function test_email_template_store_rejects_indirect_function_calls(string $payload)
    {
        $data = [
            'name' => 'Test Indirect '.uniqid(),
            'subject' => 'Test Subject',
            'content' => $payload,
            'button_text' => 'Test Button',
            'hidden' => false,
            'locale' => 'fr_FR',
        ];
        $response = $this->performAdminAction('POST', route('admin.personalization.email_templates.store'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('email_templates', ['content' => $payload]);
    }

    #[DataProvider('indirectInvocationPayloads')]
    public function test_email_template_update_rejects_indirect_function_calls(string $payload)
    {
        $this->seed(EmailTemplateSeeder::class);
        $emailTemplate = EmailTemplate::first();
        $data = [
            'name' => 'Updated Indirect',
            'subject' => 'Updated Subject',
            'content' => $payload,
            'button_text' => 'Updated Button',
            'hidden' => true,
            'locale' => 'fr_FR',
        ];
        $response = $this->performAdminAction('PUT', route('admin.personalization.email_templates.update', $emailTemplate), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('email_templates', ['id' => $emailTemplate->id, 'content' => $payload]);
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
