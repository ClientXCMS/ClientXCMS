<?php

namespace Tests\Unit\Models\Billing;

use App\Models\Billing\ConfigOption;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConfigOptionHelperTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Guards the test helper itself: it used to write to a column that does not
     * exist, and no test exercised the branch that calls it.
     */
    public function test_the_helper_attaches_the_offered_values_to_their_option(): void
    {
        foreach ([ConfigOption::TYPE_DROPDOWN, ConfigOption::TYPE_RADIO, ConfigOption::TYPE_CHECKBOX] as $type) {
            $option = $this->createOptionModel($type, 'key_'.$type);

            $this->assertCount(1, $option->fresh()->options, "the helper must attach a value to a {$type} option");
            $this->assertSame('test', $option->fresh()->options->first()->value);
        }
    }

    public function test_a_text_option_gets_no_offered_value(): void
    {
        $option = $this->createOptionModel(ConfigOption::TYPE_TEXT, 'plain_key');

        $this->assertCount(0, $option->fresh()->options);
    }
}
