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

use InvalidArgumentException;

enum ExtensionType: string
{
    case Module = 'module';
    case Addon = 'addon';
    case Theme = 'theme';
    case EmailTemplate = 'email_template';
    case InvoiceTemplate = 'invoice_template';

    private const UUID_PATTERN = '/^[a-zA-Z0-9_-]+$/';

    public static function tryFromAny(?string $type): ?self
    {
        if ($type === null) {
            return null;
        }
        $singular = str_ends_with($type, 's') ? substr($type, 0, -1) : $type;

        return self::tryFrom($type) ?? self::tryFrom($singular);
    }

    public static function fromAny(string $type): self
    {
        return self::tryFromAny($type) ?? throw new InvalidArgumentException("Unknown extension type: {$type}");
    }

    /**
     * @return array<int, string>
     */
    public static function singularValues(): array
    {
        return array_map(static fn (self $type): string => $type->value, self::cases());
    }

    /**
     * @return array<int, string>
     */
    public static function pluralValues(): array
    {
        return array_map(static fn (self $type): string => $type->plural(), self::cases());
    }

    public static function isValidUuid(string $uuid): bool
    {
        return (bool) preg_match(self::UUID_PATTERN, $uuid);
    }

    public static function assertValidUuid(string $uuid): void
    {
        if (! self::isValidUuid($uuid)) {
            throw new InvalidArgumentException('Invalid extension identifier');
        }
    }

    public function plural(): string
    {
        return $this->value.'s';
    }

    /**
     * Directory the extension is installed into, relative to the project root.
     */
    public function directory(): string
    {
        return match ($this) {
            self::Module, self::Addon => $this->plural(),
            self::Theme => 'resources/themes',
            self::EmailTemplate, self::InvoiceTemplate => 'resources/views/vendor/notifications',
        };
    }

    /**
     * Where this extension lives, relative to the project root. Templates are
     * plain files in a shared directory, everything else owns a directory.
     */
    public function path(string $uuid): string
    {
        return match ($this) {
            self::Module, self::Addon, self::Theme => $this->directory().'/'.$uuid,
            self::EmailTemplate, self::InvoiceTemplate => $this->directory().'/'.$uuid.'.blade.php',
        };
    }

    public function absolutePath(string $uuid): string
    {
        return base_path($this->path($uuid));
    }

    /**
     * Whether this type owns a dedicated directory (safe to prune obsolete
     * files 1:1 against). Templates share a directory with unrelated files,
     * so pruning them is out of scope.
     */
    public function ownsDirectory(): bool
    {
        return match ($this) {
            self::Module, self::Addon, self::Theme => true,
            self::EmailTemplate, self::InvoiceTemplate => false,
        };
    }

    /**
     * Whether a path extracted from an archive belongs to this extension. Sole
     * authority for update confinement: anything else is never written.
     */
    public function owns(string $relativePath, string $uuid): bool
    {
        self::assertValidUuid($uuid);
        $path = ltrim(str_replace('\\', '/', $relativePath), '/');
        $prefix = $this->directory().'/'.$uuid;

        return match ($this) {
            self::Module, self::Addon, self::Theme => str_starts_with($path, $prefix.'/'),
            // The product only ever reads these three files, see EmailTemplate.
            self::EmailTemplate, self::InvoiceTemplate => in_array($path, [
                $prefix.'.blade.php',
                $prefix.'_config.blade.php',
                $prefix.'_config.php',
            ], true),
        };
    }
}
