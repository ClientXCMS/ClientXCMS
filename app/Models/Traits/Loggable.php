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

namespace App\Models\Traits;

use App\Models\ActionLog;

trait Loggable
{
    public function getLogData(string $event): array
    {
        return [];
    }

    public function shouldLogAttribute(string $attribute): bool
    {
        if ($attribute === $this->getCreatedAtColumn()
            || $attribute === $this->getUpdatedAtColumn()) {
            return false;
        }

        if (count($this->getVisible()) > 0) {
            return in_array($attribute, $this->getVisible(), true);
        }

        return ! in_array($attribute, $this->getHidden(), true);
    }

    public function createLogEntries(ActionLog $log, string $event): void
    {
        if ($event !== 'updated') {
            return;
        }

        foreach ($this->getChanges() as $attribute => $value) {
            $original = $this->getOriginal($attribute);

            if ($this->shouldLogAttribute($attribute) && $this->isValidLogType($original) && $this->isValidLogType($value)) {
                $log->entries()->create([
                    'attribute' => $attribute,
                    'old_value' => $original,
                    'new_value' => $value,
                ]);
            }
        }
    }

    public function isValidLogType($value): bool
    {
        return $value === null || is_bool($value)
            || is_string($value) || is_numeric($value);
    }

    public function getLogsAction($actions = null): mixed
    {
        if (is_string($actions)) {
            $actions = [$actions];
        }
        if ($actions === null) {
            return ActionLog::where('model_id', $this->id)
                ->orderBy('created_at', 'desc')
                ->where('model', get_class($this));
        }
        $model = $this;

        return ActionLog::where('model_id', $model->id)
            ->orderBy('created_at', 'desc')
            ->where('model', get_class($model))
            ->whereIn('action', $actions);
    }
}
