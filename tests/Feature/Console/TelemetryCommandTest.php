<?php

namespace Tests\Feature\Console;

use App\Services\TelemetryService;
use Illuminate\Console\Command;
use Mockery;
use Tests\TestCase;

class TelemetryCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_disabled_telemetry_succeeds_without_calling_the_service(): void
    {
        config()->set('telemetry.enabled', false);
        $this->instance(TelemetryService::class, Mockery::mock(TelemetryService::class));

        $this->artisan('clientxcms:telemetry')
            ->expectsOutputToContain('Telemetry is disabled')
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_successful_telemetry_returns_success(): void
    {
        config()->set('telemetry.enabled', true);
        $service = Mockery::mock(TelemetryService::class);
        $service->shouldReceive('sendTelemetry')->once()->andReturnTrue();
        $this->instance(TelemetryService::class, $service);

        $this->artisan('clientxcms:telemetry')
            ->expectsOutputToContain('Telemetry data sent successfully.')
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_failed_telemetry_returns_failure(): void
    {
        config()->set('telemetry.enabled', true);
        $service = Mockery::mock(TelemetryService::class);
        $service->shouldReceive('sendTelemetry')->once()->andReturnFalse();
        $this->instance(TelemetryService::class, $service);

        $this->artisan('clientxcms:telemetry')
            ->expectsOutputToContain('Failed to send telemetry data.')
            ->assertExitCode(Command::FAILURE);
    }

    public function test_telemetry_exception_returns_failure(): void
    {
        config()->set('telemetry.enabled', true);
        $service = Mockery::mock(TelemetryService::class);
        $service->shouldReceive('sendTelemetry')->once()->andThrow(new \RuntimeException('network down'));
        $this->instance(TelemetryService::class, $service);

        $this->artisan('clientxcms:telemetry')
            ->expectsOutputToContain('Error sending telemetry data: network down')
            ->assertExitCode(Command::FAILURE);
    }
}
