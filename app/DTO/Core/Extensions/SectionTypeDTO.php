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

class SectionTypeDTO
{
    public string $uuid;

    public array $translates;

    public $sections;

    public int $id;

    public function __construct(array $json, array $sections)
    {
        $this->uuid = $json['uuid'];
        $this->translates = $json['translates'];
        $this->id = $json['id'];
        $this->sections = collect($sections)->filter(function ($section) {
            return $section->json['section_type'] == $this->id;
        });
    }

    public function name()
    {
        $locale = app()->getLocale();

        return collect($this->translates)->filter(function ($translate) use ($locale) {
            return $translate['locale'] == $locale;
        })->first(null, ['name' => $this->uuid])['name'];
    }
}
