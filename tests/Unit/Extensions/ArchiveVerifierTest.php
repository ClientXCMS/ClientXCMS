<?php

namespace Tests\Unit\Extensions;

use App\Extensions\ArchiveProof;
use App\Extensions\ArchiveVerifier;
use RuntimeException;
use Tests\TestCase;

class ArchiveVerifierTest extends TestCase
{
    private string $archive;

    private string $secretKey;

    private array $keys;

    protected function setUp(): void
    {
        parent::setUp();
        $this->archive = tempnam(sys_get_temp_dir(), 'ctx-archive-');
        file_put_contents($this->archive, 'archive payload');

        $pair = sodium_crypto_sign_keypair();
        $this->secretKey = sodium_crypto_sign_secretkey($pair);
        $this->keys = ['ctx-test' => base64_encode(sodium_crypto_sign_publickey($pair))];
    }

    protected function tearDown(): void
    {
        @unlink($this->archive);
        parent::tearDown();
    }

    private function sign(array $claims, ?string $secretKey = null): ArchiveProof
    {
        $manifest = json_encode($claims, JSON_UNESCAPED_SLASHES);

        return new ArchiveProof(
            $manifest,
            base64_encode(sodium_crypto_sign_detached($manifest, $secretKey ?? $this->secretKey)),
            'ctx-test',
        );
    }

    private function validClaims(): array
    {
        return [
            'uuid' => 'demo',
            'type' => 'module',
            'version' => 'v1.0',
            'size' => filesize($this->archive),
            'sha256' => hash_file('sha256', $this->archive),
        ];
    }

    public function test_it_accepts_an_archive_matching_its_signed_manifest(): void
    {
        $checksum = (new ArchiveVerifier($this->keys))
            ->verify($this->archive, $this->sign($this->validClaims()), 'demo', 'module');

        $this->assertSame(hash_file('sha256', $this->archive), $checksum);
    }

    public function test_it_refuses_an_archive_whose_content_changed_after_signing(): void
    {
        $proof = $this->sign($this->validClaims());
        file_put_contents($this->archive, 'archive payload tampered');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/does not match the signed checksum/');
        (new ArchiveVerifier($this->keys))->verify($this->archive, $proof, 'demo', 'module');
    }

    public function test_it_refuses_a_manifest_signed_by_another_key(): void
    {
        $intruder = sodium_crypto_sign_secretkey(sodium_crypto_sign_keypair());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/signature does not match/');
        (new ArchiveVerifier($this->keys))
            ->verify($this->archive, $this->sign($this->validClaims(), $intruder), 'demo', 'module');
    }

    public function test_it_refuses_a_manifest_edited_after_signing(): void
    {
        $proof = $this->sign($this->validClaims());
        $tampered = new ArchiveProof(
            str_replace('demo', 'evil', (string) $proof->manifest),
            $proof->signature,
            $proof->keyId,
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/signature does not match/');
        (new ArchiveVerifier($this->keys))->verify($this->archive, $tampered, 'demo', 'module');
    }

    public function test_it_refuses_an_archive_signed_for_another_extension(): void
    {
        $claims = $this->validClaims();
        $claims['uuid'] = 'other';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/signed for another extension/');
        (new ArchiveVerifier($this->keys))->verify($this->archive, $this->sign($claims), 'demo', 'module');
    }

    public function test_it_refuses_an_unknown_signing_key(): void
    {
        $proof = new ArchiveProof('{}', base64_encode('signature'), 'ctx-unknown');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/unknown key/');
        (new ArchiveVerifier($this->keys))->verify($this->archive, $proof, 'demo', 'module');
    }

    public function test_it_refuses_a_response_that_carries_no_proof_once_a_key_is_published(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/without the signed manifest/');
        (new ArchiveVerifier($this->keys))->verify($this->archive, new ArchiveProof, 'demo', 'module');
    }

    public function test_it_stays_out_of_the_way_until_the_vendor_publishes_a_key(): void
    {
        $checksum = (new ArchiveVerifier([]))->verify($this->archive, new ArchiveProof, 'demo', 'module');

        $this->assertSame(hash_file('sha256', $this->archive), $checksum);
    }
}
