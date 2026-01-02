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
 * Learn more about CLIENTXCMS License at:
 * https://clientxcms.com/eula
 *
 * Year: 2025
 */

namespace Tests\Feature\Auth;

use App\Models\Account\Customer;
use App\Models\Admin\SecurityQuestion;
use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\RefreshExtensionDatabase;
use Tests\TestCase;

class SecurityQuestionTest extends TestCase
{
    use RefreshDatabase, RefreshExtensionDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EmailTemplateSeeder::class);
    }

    // ==========================================
    // Admin CRUD Tests
    // ==========================================

    public function test_admin_can_create_security_question(): void
    {
        $admin = $this->createAdminModel();

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.security_questions.store'), [
                'question' => 'What is your pet\'s name?',
                'is_active' => true,
                'sort_order' => 1,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('security_questions', [
            'question' => 'What is your pet\'s name?',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_security_question(): void
    {
        $admin = $this->createAdminModel();
        $question = SecurityQuestion::create([
            'question' => 'Original question',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        // To deactivate a checkbox, we don't include it in the request (simulating unchecked box)
        $response = $this->actingAs($admin, 'admin')
            ->put(route('admin.security_questions.update', $question), [
                'question' => 'Updated question',
                // is_active not included = checkbox unchecked = false
                'sort_order' => 5,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('security_questions', [
            'id' => $question->id,
            'question' => 'Updated question',
            'is_active' => false,
        ]);
    }

    public function test_admin_can_delete_security_question(): void
    {
        $admin = $this->createAdminModel();
        $question = SecurityQuestion::create([
            'question' => 'Question to delete',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->delete(route('admin.security_questions.destroy', $question));

        $response->assertRedirect();
        $this->assertDatabaseMissing('security_questions', [
            'id' => $question->id,
        ]);
    }

    // ==========================================
    // Registration Tests
    // ==========================================

    public function test_new_users_can_register_with_security_question(): void
    {
        $question = SecurityQuestion::create([
            'question' => 'What is your favorite color?',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $response = $this->post('/register', [
            'firstname' => 'Test',
            'lastname' => 'User',
            'zipcode' => '59100',
            'region' => 'Nord',
            'country' => 'FR',
            'email' => 'test@example.com',
            'address' => 'test street',
            'city' => 'test city',
            'phone' => '0176010380',
            'password' => 'password',
            'password_confirmation' => 'password',
            'security_question_id' => $question->id,
            'security_answer' => 'Blue',
        ]);

        $this->assertAuthenticated();

        $customer = Customer::where('email', 'test@example.com')->first();
        $this->assertNotNull($customer);
        $this->assertEquals($question->id, $customer->security_question_id);
        $this->assertTrue($customer->hasSecurityQuestion());
        $this->assertTrue($customer->verifySecurityAnswer('Blue'));
        $this->assertTrue($customer->verifySecurityAnswer('blue')); // Case insensitive
        $this->assertTrue($customer->verifySecurityAnswer('BLUE')); // Case insensitive
    }

    public function test_new_users_can_register_without_security_question(): void
    {
        $response = $this->post('/register', [
            'firstname' => 'Test',
            'lastname' => 'User',
            'zipcode' => '59100',
            'region' => 'Nord',
            'country' => 'FR',
            'email' => 'test2@example.com',
            'address' => 'test street',
            'city' => 'test city',
            'phone' => '0176010380',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();

        $customer = Customer::where('email', 'test2@example.com')->first();
        $this->assertNotNull($customer);
        $this->assertNull($customer->security_question_id);
        $this->assertFalse($customer->hasSecurityQuestion());
    }

    // ==========================================
    // Password Change Tests
    // ==========================================

    public function test_password_change_requires_security_answer_if_question_set(): void
    {
        $question = SecurityQuestion::create([
            'question' => 'What is your favorite color?',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $customer = Customer::factory()->create([
            'password' => bcrypt('oldpassword'),
        ]);
        $customer->setSecurityQuestion($question->id, 'red');

        $response = $this->actingAs($customer, 'web')
            ->post(route('front.profile.password'), [
                'password' => 'newpassword',
                'password_confirmation' => 'newpassword',
                'currentpassword' => 'oldpassword',
                // Missing security_answer
            ]);

        $response->assertSessionHasErrors('security_answer');
    }

    public function test_password_change_fails_with_wrong_security_answer(): void
    {
        $question = SecurityQuestion::create([
            'question' => 'What is your favorite color?',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $customer = Customer::factory()->create([
            'password' => bcrypt('oldpassword'),
        ]);
        $customer->setSecurityQuestion($question->id, 'red');

        $response = $this->actingAs($customer, 'web')
            ->post(route('front.profile.password'), [
                'password' => 'newpassword',
                'password_confirmation' => 'newpassword',
                'currentpassword' => 'oldpassword',
                'security_answer' => 'wrong answer',
            ]);

        $response->assertSessionHasErrors('security_answer');
    }

    public function test_password_change_succeeds_with_correct_security_answer(): void
    {
        $question = SecurityQuestion::create([
            'question' => 'What is your favorite color?',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $customer = Customer::factory()->create([
            'password' => bcrypt('oldpassword'),
        ]);
        $customer->setSecurityQuestion($question->id, 'red');

        $response = $this->actingAs($customer, 'web')
            ->post(route('front.profile.password'), [
                'password' => 'newpassword',
                'password_confirmation' => 'newpassword',
                'currentpassword' => 'oldpassword',
                'security_answer' => 'RED', // Case insensitive
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('front.profile.index'));
    }

    public function test_password_change_succeeds_without_security_answer_if_no_question(): void
    {
        $customer = Customer::factory()->create([
            'password' => bcrypt('oldpassword'),
        ]);

        $response = $this->actingAs($customer, 'web')
            ->post(route('front.profile.password'), [
                'password' => 'newpassword',
                'password_confirmation' => 'newpassword',
                'currentpassword' => 'oldpassword',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('front.profile.index'));
    }

    // ==========================================
    // Existing Account Security Question Setup
    // ==========================================

    public function test_existing_user_can_set_security_question(): void
    {
        $question = SecurityQuestion::create([
            'question' => 'What is your mother\'s maiden name?',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $customer = Customer::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $this->assertFalse($customer->hasSecurityQuestion());

        $response = $this->actingAs($customer, 'web')
            ->post(route('front.profile.security_question'), [
                'security_question_id' => $question->id,
                'security_answer' => 'Smith',
                'currentpassword' => 'password',
            ]);

        $response->assertRedirect(route('front.profile.index'));

        $customer->refresh();
        $this->assertTrue($customer->hasSecurityQuestion());
        $this->assertEquals($question->id, $customer->security_question_id);
        $this->assertTrue($customer->verifySecurityAnswer('smith'));
    }

    public function test_set_security_question_requires_password(): void
    {
        $question = SecurityQuestion::create([
            'question' => 'What is your mother\'s maiden name?',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $customer = Customer::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $response = $this->actingAs($customer, 'web')
            ->post(route('front.profile.security_question'), [
                'security_question_id' => $question->id,
                'security_answer' => 'Smith',
                'currentpassword' => 'wrongpassword',
            ]);

        $response->assertSessionHasErrors('currentpassword');
    }

    // ==========================================
    // Admin Reset Tests
    // ==========================================

    public function test_admin_can_reset_customer_security_question(): void
    {
        $admin = $this->createAdminModel();
        $question = SecurityQuestion::create([
            'question' => 'What is your pet\'s name?',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $customer = Customer::factory()->create();
        $customer->setSecurityQuestion($question->id, 'Fluffy');

        $this->assertTrue($customer->hasSecurityQuestion());

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.customers.action', [
                'customer' => $customer,
                'action' => 'resetSecurityQuestion',
            ]));

        $response->assertRedirect();

        $customer->refresh();
        $this->assertFalse($customer->hasSecurityQuestion());
        $this->assertNull($customer->security_question_id);
        $this->assertNull($customer->security_answer);
    }

    // ==========================================
    // Model Method Tests
    // ==========================================

    public function test_security_answer_verification_is_case_insensitive(): void
    {
        $question = SecurityQuestion::create([
            'question' => 'Test question',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $customer = Customer::factory()->create();
        $customer->setSecurityQuestion($question->id, 'MySecretAnswer');

        $this->assertTrue($customer->verifySecurityAnswer('mysecretanswer'));
        $this->assertTrue($customer->verifySecurityAnswer('MYSECRETANSWER'));
        $this->assertTrue($customer->verifySecurityAnswer('MySecretAnswer'));
        $this->assertTrue($customer->verifySecurityAnswer('  mysecretanswer  ')); // Trim
        $this->assertFalse($customer->verifySecurityAnswer('wrong'));
    }

    public function test_verify_security_answer_returns_true_if_no_question(): void
    {
        $customer = Customer::factory()->create();

        $this->assertFalse($customer->hasSecurityQuestion());
        $this->assertTrue($customer->verifySecurityAnswer('anything')); // Should pass if no question set
    }

    public function test_security_question_reset_clears_both_fields(): void
    {
        $question = SecurityQuestion::create([
            'question' => 'Test question',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $customer = Customer::factory()->create();
        $customer->setSecurityQuestion($question->id, 'answer');

        $this->assertTrue($customer->hasSecurityQuestion());

        $customer->resetSecurityQuestion();

        $this->assertFalse($customer->hasSecurityQuestion());
        $this->assertNull($customer->security_question_id);
        $this->assertNull($customer->security_answer);
    }
}
