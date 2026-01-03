<?php

/*
 * This file is part of the CLIENTXCMS project.
 * This is the Client API PaymentMethodController.
 *
 * Year: 2025
 */

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Billing\Gateway;
use App\Services\Store\GatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * @OA\Tag(
 *     name="Customer Payment Methods",
 *     description="Payment method endpoints for client API"
 * )
 */
class PaymentMethodController extends Controller
{
    /**
     * @OA\Get(
     *     path="/client/payment-methods",
     *     summary="List customer's payment methods",
     *     tags={"Customer Payment Methods"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of payment methods"
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $customer = $request->user();
        $gateways = GatewayService::getAvailable();

        $sources = collect($gateways)->map(function ($gateway) use ($customer) {
            return $gateway->paymentType()->getSources($customer);
        })->flatten();

        $defaultPaymentMethod = $customer->default_payment_method;

        return response()->json([
            'data' => $sources->map(fn ($source) => [
                'id' => $source->id,
                'gateway_uuid' => $source->gateway_uuid,
                'type' => $source->type ?? 'card',
                'last_four' => $source->last_four ?? null,
                'brand' => $source->brand ?? null,
                'expiry_month' => $source->expiry_month ?? null,
                'expiry_year' => $source->expiry_year ?? null,
                'is_default' => $source->id === $defaultPaymentMethod,
                'created_at' => $source->created_at?->toIso8601String(),
            ]),
            'default_payment_method' => $defaultPaymentMethod,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/client/payment-methods/gateways",
     *     summary="List available gateways that support payment methods",
     *     tags={"Customer Payment Methods"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of gateways"
     *     )
     * )
     */
    public function gateways(Request $request): JsonResponse
    {
        $gateways = GatewayService::getAvailable();

        $gatewaysWithSources = collect($gateways)->filter(function ($gateway) {
            return ! empty($gateway->paymentType()->sourceForm());
        });

        return response()->json([
            'data' => $gatewaysWithSources->map(fn ($g) => [
                'uuid' => $g->uuid,
                'name' => $g->name,
            ])->values(),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/client/payment-methods/{gateway}",
     *     summary="Add a new payment method",
     *     tags={"Customer Payment Methods"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="gateway",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Gateway-specific payment method data"
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Payment method added"
     *     ),
     *     @OA\Response(response=400, description="Gateway does not support payment methods")
     * )
     */
    public function add(Request $request, Gateway $gateway): JsonResponse
    {
        if (empty($gateway->paymentType()->sourceForm())) {
            return response()->json([
                'error' => __('client.payment-methods.errors.not_supported'),
            ], 400);
        }

        try {
            $result = $gateway->paymentType()->addSource($request);

            // Clear cache
            Cache::forget('payment_methods_'.$request->user()->id);

            // Check if result is a redirect (for 3DS, etc.)
            if ($result instanceof \Illuminate\Http\RedirectResponse) {
                return response()->json([
                    'redirect_url' => $result->getTargetUrl(),
                    'message' => __('client.payment-methods.redirect_required'),
                ]);
            }

            return response()->json([
                'message' => __('client.payment-methods.success'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/client/payment-methods/{source}/default",
     *     summary="Set default payment method",
     *     tags={"Customer Payment Methods"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="source",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Default payment method set"
     *     ),
     *     @OA\Response(response=404, description="Payment method not found")
     * )
     */
    public function setDefault(Request $request, string $source): JsonResponse
    {
        $customer = $request->user();
        $gateways = GatewayService::getAvailable();

        $gateway = collect($gateways)->first(function ($gateway) use ($source, $customer) {
            return $gateway->paymentType()->getSource($customer, $source);
        });

        if (! $gateway) {
            return response()->json([
                'error' => __('client.payment-methods.errors.not_found'),
            ], 404);
        }

        $customer->setDefaultPaymentMethod($source);

        return response()->json([
            'message' => __('client.payment-methods.defaultsucces'),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/client/payment-methods/{source}",
     *     summary="Delete a payment method",
     *     tags={"Customer Payment Methods"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="source",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Payment method deleted"
     *     ),
     *     @OA\Response(response=404, description="Payment method not found")
     * )
     */
    public function delete(Request $request, string $source): JsonResponse
    {
        $customer = $request->user();
        $paymentMethod = $customer->paymentMethods()->where('id', $source)->first();

        if (! $paymentMethod) {
            return response()->json([
                'error' => __('client.payment-methods.errors.not_found'),
            ], 404);
        }

        $gateway = Gateway::where('uuid', $paymentMethod->gateway_uuid)->first();
        if (! $gateway) {
            return response()->json([
                'error' => __('client.payment-methods.errors.not_found'),
            ], 404);
        }

        try {
            $gateway->paymentType()->removeSource($paymentMethod);
            Cache::forget('payment_methods_'.$customer->id);

            return response()->json([
                'message' => __('client.payment-methods.deleted'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
