<?php

namespace Tests\Unit\Services;

use App\Models\Account\Customer;
use App\Rules\PhoneRule;
use App\Services\Account\AccountEditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class PhoneValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that empty/null values pass validation (nullable handling).
     */
    public function test_empty_values_pass_validation(): void
    {
        $rule = (new PhoneRule)->country('FR');

        $this->assertTrue($rule->passes('phone', ''));
        $this->assertTrue($rule->passes('phone', null));
    }

    /**
     * Test that AccountEditService returns proper rules structure.
     */
    public function test_account_edit_service_returns_proper_rules_structure(): void
    {
        $rules = AccountEditService::rules('FR');

        $this->assertArrayHasKey('firstname', $rules);
        $this->assertArrayHasKey('lastname', $rules);
        $this->assertArrayHasKey('phone', $rules);
        $this->assertArrayHasKey('country', $rules);
        $this->assertArrayHasKey('zipcode', $rules);
        $this->assertArrayHasKey('locale', $rules);
    }

    /**
     * Test that AccountEditService includes email rules when requested.
     */
    public function test_account_edit_service_includes_email_rules(): void
    {
        $rulesWithEmail = AccountEditService::rules('FR', email: true);
        $rulesWithoutEmail = AccountEditService::rules('FR', email: false);

        $this->assertArrayHasKey('email', $rulesWithEmail);
        $this->assertContains('required', $rulesWithEmail['email']);

        // When email is false, the email key should not be present
        $this->assertArrayNotHasKey('email', $rulesWithoutEmail);
    }

    /**
     * Test that AccountEditService includes password rules when requested.
     */
    public function test_account_edit_service_includes_password_rules(): void
    {
        $rulesWithPassword = AccountEditService::rules('FR', password: true);
        $rulesWithoutPassword = AccountEditService::rules('FR', password: false);

        $this->assertArrayHasKey('password', $rulesWithPassword);
        $this->assertArrayNotHasKey('password', $rulesWithoutPassword);
    }

    /**
     * Test that validation passes for 06 and 07 numbers through full validator.
     */
    public function test_full_validation_with_06_number(): void
    {
        $data = $this->getValidCustomerData();
        $data['phone'] = '0612345678';

        $rules = AccountEditService::rules('FR', email: true);
        $validator = Validator::make($data, $rules);

        $this->assertFalse(
            $validator->fails(),
            'Validation should pass for 06 number. Errors: ' . json_encode($validator->errors()->toArray())
        );
    }

    /**
     * Test that validation passes for 07 numbers through full validator.
     */
    public function test_full_validation_with_07_number(): void
    {
        $data = $this->getValidCustomerData();
        $data['phone'] = '0712345678';

        $rules = AccountEditService::rules('FR', email: true);
        $validator = Validator::make($data, $rules);

        $this->assertFalse(
            $validator->fails(),
            'Validation should pass for 07 number. Errors: ' . json_encode($validator->errors()->toArray())
        );
    }

    /**
     * Test that validation passes for international format 07 numbers.
     */
    public function test_full_validation_with_international_07_number(): void
    {
        $data = $this->getValidCustomerData();
        $data['phone'] = '+33712345678';

        $rules = AccountEditService::rules('FR', email: true);
        $validator = Validator::make($data, $rules);

        $this->assertFalse(
            $validator->fails(),
            'Validation should pass for international 07 number. Errors: ' . json_encode($validator->errors()->toArray())
        );
    }

    /**
     * Test phone uniqueness validation works correctly.
     */
    public function test_phone_uniqueness_validation(): void
    {
        // Create a customer with a phone number
        $existingCustomer = Customer::create([
            'firstname' => 'Existing',
            'lastname' => 'User',
            'email' => 'existing@example.com',
            'password' => 'password',
            'address' => '123 Test St',
            'city' => 'Test City',
            'country' => 'FR',
            'region' => 'Test Region',
            'zipcode' => '75000',
            'phone' => '+33612345678',
        ]);

        // Try to create another with the same phone
        $data = $this->getValidCustomerData();
        $data['phone'] = '+33612345678';
        $data['email'] = 'new@example.com';

        $rules = AccountEditService::rules('FR', email: true);
        $validator = Validator::make($data, $rules);

        $this->assertTrue(
            $validator->fails(),
            'Validation should fail for duplicate phone number'
        );
        $this->assertArrayHasKey('phone', $validator->errors()->toArray());
    }

    /**
     * Test phone uniqueness ignores current customer.
     */
    public function test_phone_uniqueness_ignores_current_customer(): void
    {
        // Create a customer with a phone number
        $existingCustomer = Customer::create([
            'firstname' => 'Existing',
            'lastname' => 'User',
            'email' => 'existing@example.com',
            'password' => 'password',
            'address' => '123 Test St',
            'city' => 'Test City',
            'country' => 'FR',
            'region' => 'Test Region',
            'zipcode' => '75000',
            'phone' => '+33612345678',
        ]);

        // Same phone should be allowed when updating the same customer
        $data = $this->getValidCustomerData();
        $data['phone'] = '+33612345678';
        $data['email'] = 'existing@example.com';

        $rules = AccountEditService::rules('FR', email: true, except: $existingCustomer->id);
        $validator = Validator::make($data, $rules);

        $this->assertFalse(
            $validator->fails(),
            'Validation should pass when updating same customer. Errors: ' . json_encode($validator->errors()->toArray())
        );
    }

    /**
     * Data provider for valid French 06 mobile numbers.
     */
    public static function french06MobileNumbersProvider(): array
    {
        return [
            ['0612345678'],
            ['06 12 34 56 78'],
            ['06-12-34-56-78'],
            ['06.12.34.56.78'],
        ];
    }

    /**
     * Data provider for valid French 07 mobile numbers.
     */
    public static function french07MobileNumbersProvider(): array
    {
        return [
            ['0712345678'],
            ['07 12 34 56 78'],
            ['07-12-34-56-78'],
            ['07.12.34.56.78'],
        ];
    }

    /**
     * Data provider for international formatted French numbers.
     */
    public static function internationalFrenchNumbersProvider(): array
    {
        return [
            ['+33612345678'],
            ['+33712345678'],
            ['+33 6 12 34 56 78'],
            ['+33 7 12 34 56 78'],
            ['0033612345678'],
            ['0033712345678'],
        ];
    }

    /**
     * Data provider for invalid phone numbers.
     * Note: libphonenumber is quite lenient, so we only test clearly unparseable numbers.
     */
    public static function invalidPhoneNumbersProvider(): array
    {
        return [
            ['123'],           // Too short
            ['abcdefghij'],    // Not a number
        ];
    }

    /**
     * Get valid customer data for testing.
     */
    private function getValidCustomerData(): array
    {
        return [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'test@example.com',
            'address' => '123 Test Street',
            'city' => 'Paris',
            'zipcode' => '75001',
            'region' => 'Île-de-France',
            'country' => 'FR',
            'phone' => '0612345678',
        ];
    }
}
