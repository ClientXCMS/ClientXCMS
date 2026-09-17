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
        $response->assertViewIs('admin.personalization.email-templates.index');
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
        $response->assertViewIs('admin.personalization.email-templates.show');
    }

    public function test_email_template_create()
    {
        $this->seed(EmailTemplateSeeder::class);
        $response = $this->performAdminAction('GET', route('admin.personalization.email_templates.create'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.personalization.email-templates.create');
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

    /**
     * These used to assert a rejection. Nothing is rejected anymore because
     * nothing is executed: the renderer has a closed grammar, so a function call
     * is text like any other. Proof that it stays inert lives in
     * TemplateIsNotExecutedTest, which goes through the real send path; here we
     * only check the controller stores what it was handed.
     */
    public function test_email_template_store_keeps_a_function_call_as_text()
    {
        $data = [
            'name' => 'Test Template',
            'subject' => 'Test Subject',
            'content' => "{{ system('id') }}",
            'button_text' => 'Test Button',
            'hidden' => false,
            'locale' => 'fr_FR',
        ];

        $this->performAdminAction('POST', route('admin.personalization.email_templates.store'), $data);

        $this->assertDatabaseHas('email_templates', ['name' => 'Test Template', 'content' => "{{ system('id') }}"]);
    }

    public function test_email_template_update_keeps_a_function_call_as_text()
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

        $this->performAdminAction('PUT', route('admin.personalization.email_templates.update', $emailTemplate), $data);

        $this->assertDatabaseHas('email_templates', ['id' => $emailTemplate->id, 'content' => "{{ file_get_contents('.env') }}"]);
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
    public function test_email_template_store_keeps_an_indirect_call_as_text(string $payload)
    {
        $data = [
            'name' => 'Test Indirect '.uniqid(),
            'subject' => 'Test Subject',
            'content' => $payload,
            'button_text' => 'Test Button',
            'hidden' => false,
            'locale' => 'fr_FR',
        ];

        $this->performAdminAction('POST', route('admin.personalization.email_templates.store'), $data);

        $this->assertDatabaseHas('email_templates', ['content' => $payload]);
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
