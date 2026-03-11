<?php

use App\Addons\HelpdeskBridge\Http\Controllers\Webhook\HelpdeskInboundEmailController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/helpdesk/inbound-email', [HelpdeskInboundEmailController::class, 'handle'])
    ->name('webhooks.helpdesk.inbound-email');
