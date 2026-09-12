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

use Psr\Http\Message\ResponseInterface;

/**
 * What the update server claims about the archive it just served. Everything
 * here is attacker-controlled until ArchiveVerifier has checked it.
 */
final readonly class ArchiveProof
{
    public function __construct(
        public ?string $manifest = null,
        public ?string $signature = null,
        public ?string $keyId = null,
    ) {}

    public static function fromResponse(?ResponseInterface $response): self
    {
        if ($response === null) {
            return new self;
        }

        return new self(
            self::decodeManifest($response->getHeaderLine('ctx-manifest')),
            self::header($response, 'ctx-signature'),
            self::header($response, 'ctx-key-id'),
        );
    }

    public function isEmpty(): bool
    {
        return $this->manifest === null && $this->signature === null;
    }

    /**
     * @return array<string, mixed>
     */
    public function claims(): array
    {
        if ($this->manifest === null) {
            return [];
        }
        $claims = json_decode($this->manifest, true);

        return is_array($claims) ? $claims : [];
    }

    private static function decodeManifest(string $value): ?string
    {
        if ($value === '') {
            return null;
        }
        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return $decoded === false ? null : $decoded;
    }

    private static function header(ResponseInterface $response, string $name): ?string
    {
        $value = trim($response->getHeaderLine($name));

        return $value === '' ? null : $value;
    }
}
