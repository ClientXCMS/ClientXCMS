<?php

namespace App\Addons\HelpdeskBridge\Http\Controllers\Webhook;

use App\Addons\HelpdeskBridge\Services\Helpdesk\InboundEmailBridgeService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HelpdeskInboundEmailController extends Controller
{
    public function __construct(private readonly InboundEmailBridgeService $bridge)
    {
    }

    public function handle(Request $request): JsonResponse
    {
        $result = $this->bridge->handle($request);

        return response()->json($result, $result['status']);
    }
}
