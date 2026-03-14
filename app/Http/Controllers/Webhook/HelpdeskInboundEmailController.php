<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Services\Helpdesk\InboundEmailBridgeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HelpdeskInboundEmailController extends Controller
{
    public function __invoke(Request $request, InboundEmailBridgeService $service): JsonResponse
    {
        $result = $service->handle($request);

        return response()->json($result['payload'], $result['status']);
    }
}
