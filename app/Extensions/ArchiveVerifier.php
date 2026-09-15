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

namespace App\Extensions;

use RuntimeException;

/**
 * Checks that a downloaded archive is the one the vendor published, using the
 * signed manifest served alongside it. Publishing a key here makes the proof
 * mandatory, so a stripped-down response can never downgrade the check.
 */
class ArchiveVerifier
{
    /**
     * Ed25519 public keys published by the vendor, as key_id => base64 key.
     *
     * @var array<string, string>
     */
    private const PUBLIC_KEYS = [];

    /**
     * @param  array<string, string>|null  $publicKeys
     */
    public function __construct(private readonly ?array $publicKeys = null) {}

    /**
     * @return string the archive checksum, for auditing
     */
    public function verify(string $file, ArchiveProof $proof, string $uuid, string $type): string
    {
        $checksum = hash_file('sha256', $file);
        if ($checksum === false) {
            throw new RuntimeException("Unable to read archive: {$file}");
        }

        $keys = $this->publicKeys ?? self::PUBLIC_KEYS;
        if ($keys === []) {
            return $checksum;
        }

        if ($proof->isEmpty()) {
            throw new RuntimeException('Archive came without the signed manifest the update server must provide');
        }
        if (! extension_loaded('sodium')) {
            throw new RuntimeException('The sodium extension is required to verify update signatures');
        }
        if ($proof->manifest === null || $proof->signature === null || $proof->keyId === null) {
            throw new RuntimeException('Archive proof is incomplete');
        }
        if (! array_key_exists($proof->keyId, $keys)) {
            throw new RuntimeException("Archive was signed with an unknown key: {$proof->keyId}");
        }

        $publicKey = base64_decode($keys[$proof->keyId], true);
        $signature = base64_decode($proof->signature, true);
        if ($publicKey === false || $signature === false
            || ! sodium_crypto_sign_verify_detached($signature, $proof->manifest, $publicKey)) {
            throw new RuntimeException('Archive signature does not match the manifest');
        }

        $this->assertClaimsDescribeTheArchive($proof->claims(), $file, $checksum, $uuid, $type);

        return $checksum;
    }

    /**
     * @param  array<string, mixed>  $claims
     */
    private function assertClaimsDescribeTheArchive(
        array $claims,
        string $file,
        string $checksum,
        string $uuid,
        string $type
    ): void {
        if (! isset($claims['sha256']) || ! hash_equals((string) $claims['sha256'], $checksum)) {
            throw new RuntimeException('Archive content does not match the signed checksum');
        }
        if (isset($claims['size']) && (int) $claims['size'] !== filesize($file)) {
            throw new RuntimeException('Archive size does not match the signed manifest');
        }
        if (! isset($claims['uuid']) || $claims['uuid'] !== $uuid) {
            throw new RuntimeException('Archive was signed for another extension');
        }
        if (isset($claims['type']) && $claims['type'] !== $type) {
            throw new RuntimeException('Archive was signed for another extension type');
        }
    }
}
