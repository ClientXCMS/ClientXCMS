<?php

return [
    'connection' => env('QUEUE_MONITOR_CONNECTION', env('QUEUE_CONNECTION', 'sync')),
    'queue' => env('QUEUE_MONITOR_QUEUE', 'default'),
    'heartbeat_after_minutes' => (int) env('QUEUE_MONITOR_HEARTBEAT_MINUTES', 5),
    'waiting_after_minutes' => (int) env('QUEUE_MONITOR_WAITING_MINUTES', 15),
];
