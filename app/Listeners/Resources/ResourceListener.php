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

namespace App\Listeners\Resources;

use App\Events\Resources\AbstractResourceEvent;
use App\Models\ActionLog;

class ResourceListener
{
    public function handle(AbstractResourceEvent $event): void
    {
        $eventName = $event->event;
        $model = $event->model;
        $action = 'resource_'.$eventName;
        if (empty($model->getChanges())) {
            return;
        }
        $staffId = auth('admin')->id();
        $customerId = auth('web')->id();
        if (! method_exists($model, 'getLogData')) {
            return;
        }
        $log = ActionLog::log($action, get_class($model), $model->getKey(), $staffId, $customerId, $model->getLogData($eventName));

        if ($log !== null) {
            $model->createLogEntries($log, $eventName);
        }
    }
}
