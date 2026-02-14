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

namespace App\DTO\Core\Extensions;

class SectionDefinition
{
    use SectionFieldPresets;

    private const DEFAULT_THUMBNAIL = 'https://api-nextgen.clientxcms.com/assets/2efe449a-a399-4f2c-8b1a-627b0a712c82';

    private string $uuid;

    private string $path;

    private bool $isDefault = false;

    private string $defaultUrl = '/';

    private string $thumbnail;

    private bool $isConfigurable = true;

    private bool $isProtected = true;

    /** @var SectionField[] */
    private array $fields = [];

    private ?string $extensionNeeded = null;

    private function __construct(string $uuid)
    {
        $this->uuid = $uuid;
        $this->path = "sections.{$uuid}";
        $this->thumbnail = self::DEFAULT_THUMBNAIL;
    }

    public static function make(string $uuid): self
    {
        return new self($uuid);
    }

    public function path(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function default(bool $default = true): self
    {
        $this->isDefault = $default;

        return $this;
    }

    public function defaultUrl(string $url): self
    {
        $this->defaultUrl = $url;

        return $this;
    }

    public function thumbnail(string $url): self
    {
        $this->thumbnail = $url;

        return $this;
    }

    public function configurable(bool $configurable = true): self
    {
        $this->isConfigurable = $configurable;

        return $this;
    }

    public function protected(bool $protected = true): self
    {
        $this->isProtected = $protected;

        return $this;
    }

    public function fields(array $fields): self
    {
        $this->fields = $fields;

        return $this;
    }

    public function field(SectionField $field): self
    {
        $this->fields[] = $field;

        return $this;
    }

    public function extensionNeeded(string $extension): self
    {
        $this->extensionNeeded = $extension;

        return $this;
    }
    public function toArray(): array
    {
        $data = [
            'thumbnail' => $this->thumbnail,
            'path' => $this->path,
            'uuid' => $this->uuid,
            'default' => $this->isDefault,
            'default_url' => $this->defaultUrl
        ];

        if ($this->isConfigurable && ! empty($this->fields)) {
            $data['configurable'] = true;
            $data['fields'] = array_map(
                fn(SectionField $field) => $field->toArray(),
                $this->fields
            );
        }

        if ($this->extensionNeeded !== null) {
            $data['extension_needed'] = $this->extensionNeeded;
        }

        $data['protected'] = $this->isProtected;

        return $data;
    }
}
