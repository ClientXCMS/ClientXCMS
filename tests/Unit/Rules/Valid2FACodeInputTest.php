<?php

namespace Tests\Unit\Rules;

use App\Rules\Valid2FACodeInput;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class Valid2FACodeInputTest extends TestCase
{
    public static function acceptedProvider(): array
    {
        return [
            'TOTP / email six digits' => ['123456'],
            'TOTP all zeros' => ['000000'],
            'TOTP max value' => ['999999'],
            'recovery code lowercase' => ['deadbeef-cafebabe-12345678'],
            'recovery code mixed-case normalized' => ['DEADBEEF-CAFEBABE-12345678'],
            'whitespace tolerated (TOTP)' => [' 123 456 '],
        ];
    }

    public static function rejectedProvider(): array
    {
        return [
            'empty string' => [''],
            'too short' => ['12345'],
            'too long' => ['1234567'],
            'non numeric six chars' => ['12345a'],
            'recovery wrong segment count' => ['deadbeef-cafebabe'],
            'recovery wrong segment length' => ['deadbee-cafebabe-12345678'],
            'recovery non hex' => ['zzzzzzzz-cafebabe-12345678'],
            'TOTP with internal dash' => ['123-456'],
            'sql injection attempt' => ["123456' OR '1"],
            'xss attempt' => ['<script>alert(1)</script>'],
            'long bcrypt-cap payload' => [str_repeat('1', 200)],
        ];
    }

    #[DataProvider('acceptedProvider')]
    public function test_accepts_valid_shape(string $value): void
    {
        $this->assertFalse(
            $this->capturesFailure($value),
            "Expected to accept legitimate 2FA input shape: {$value}"
        );
    }

    #[DataProvider('rejectedProvider')]
    public function test_rejects_invalid_shape(string $value): void
    {
        $this->assertTrue(
            $this->capturesFailure($value),
            "Expected to reject malformed 2FA input: {$value}"
        );
    }

    public function test_rejects_non_string_value(): void
    {
        $this->assertTrue($this->capturesFailure(123456));
        $this->assertTrue($this->capturesFailure(null));
        $this->assertTrue($this->capturesFailure(['123456']));
    }

    private function capturesFailure(mixed $value): bool
    {
        $failed = false;
        (new Valid2FACodeInput)->validate(
            '2fa',
            $value,
            function () use (&$failed) {
                $failed = true;

                return new class
                {
                    public function translate(): self
                    {
                        return $this;
                    }
                };
            }
        );

        return $failed;
    }
}
