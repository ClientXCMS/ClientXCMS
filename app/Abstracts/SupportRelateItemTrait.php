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
 * Year: 2025
 */
namespace App\Abstracts;

trait SupportRelateItemTrait
{
    public function relatedName(): string
    {
        return 'Related Name';
    }

    public function relatedId(): int
    {
        return $this->id;
    }

    public function relatedLink(): string
    {
        return route('admin.'.$this->relatedType().'s.show', $this->relatedId());
    }

    public function relatedIcon(): string
    {
        switch ($this->relatedType()) {
            case 'invoice':
                return 'bi bi-file-earmark-text';
            case 'service':
                return 'bi bi-cube';
            default:
                return 'bi bi-question-circle';
        }
    }

    public function relatedType(): string
    {
        return strtolower(class_basename($this));
    }
}
