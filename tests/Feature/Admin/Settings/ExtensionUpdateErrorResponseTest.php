<?php

namespace Tests\Feature\Admin\Settings;

use App\Http\Controllers\Admin\Settings\SettingsExtensionController;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

class ExtensionUpdateErrorResponseTest extends TestCase
{
    public function test_update_error_hides_technical_details_when_debug_is_disabled(): void
    {
        config()->set('app.debug', false);

        $response = $this->invokeErrorResponse(new RuntimeException('Sensitive path'));

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame([
            'error' => __('extensions.flash.update_failed'),
        ], $response->getData(true));
    }

    public function test_update_error_exposes_technical_details_when_debug_is_enabled(): void
    {
        config()->set('app.debug', true);

        $response = $this->invokeErrorResponse(new RuntimeException('Unable to open zip file'));

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame([
            'error' => __('extensions.flash.update_failed'),
            'debug_details' => 'Unable to open zip file',
        ], $response->getData(true));
    }

    private function invokeErrorResponse(RuntimeException $exception)
    {
        $method = new ReflectionMethod(SettingsExtensionController::class, 'extensionUpdateErrorResponse');
        $method->setAccessible(true);

        return $method->invoke(new SettingsExtensionController, $exception);
    }
}
