<?php

namespace Tests\Unit\Core\Domain;

use App\Core\Domain\Concerns\SubmitsDomainTransfers;
use App\Models\Provisioning\Service;
use RuntimeException;
use Tests\TestCase;

class SubmitsDomainTransfersTest extends TestCase
{
    public function test_auth_code_is_required_and_trimmed(): void
    {
        $transfers = $this->transfers();

        $this->assertSame('EPP-123', $transfers->call('transferAuthCode', $this->service(['auth_code' => ' EPP-123 '])));
        foreach ([[], ['auth_code' => '   '], ['auth_code' => null]] as $data) {
            try {
                $transfers->call('transferAuthCode', $this->service($data));
                $this->fail('A missing authorization code should have been rejected');
            } catch (RuntimeException $e) {
                $this->assertSame('Authorization code is required', $e->getMessage());
            }
        }
    }

    public function test_submitted_transfer_is_pending_and_forgets_the_auth_code(): void
    {
        $service = $this->service(['domain' => 'example.com', 'auth_code' => 'EPP-123']);

        $this->transfers()->call('markTransferSubmitted', $service, 42);

        $this->assertSame(['domain' => 'example.com', 'registrar_id' => '42', 'registrar_status' => 'transfer_pending', 'transfer_submitted' => true], $service->data);
    }

    public function test_missing_registrar_id_keeps_the_existing_one(): void
    {
        $service = $this->service(['registrar_id' => 'old', 'auth_code' => 'EPP-123']);

        $this->transfers()->call('markTransferSubmitted', $service, '');

        $this->assertSame('old', $service->data['registrar_id']);
    }

    private function service(array $data): Service
    {
        $service = new class extends Service
        {
            public function save(array $options = []): bool
            {
                return true;
            }
        };
        $service->data = $data;

        return $service;
    }

    private function transfers(): object
    {
        return new class
        {
            use SubmitsDomainTransfers;

            public function call(string $method, mixed ...$arguments): mixed
            {
                return $this->{$method}(...$arguments);
            }
        };
    }
}
