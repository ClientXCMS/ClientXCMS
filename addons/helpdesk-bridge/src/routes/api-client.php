<?php

use App\Http\Controllers\Webhook\HelpdeskInboundEmailController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/helpdesk/inbound-email', HelpdeskInboundEmailController::class)
    ->middleware('throttle:30,1')
    ->name('helpdesk.inbound-email');
