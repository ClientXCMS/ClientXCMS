<?php

namespace Database\Factories\Core;

use App\Models\Account\Customer;
use App\Models\Account\EmailMessage;
use App\Models\Admin\EmailTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class EmailMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'recipient_id' => Customer::first()->id,
            'subject' => 'test',
            'content' => 'test',
            'recipient' => 'test@clientxcms.com',
            'template' => EmailTemplate::first()->id,
        ];
    }

    public function modelName()
    {
        return EmailMessage::class;
    }
}
