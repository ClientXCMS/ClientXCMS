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

namespace App\Http\Controllers\Api\Client;

use App\Exceptions\WrongPaymentException;
use App\Http\Controllers\Controller;
use App\Models\Billing\Gateway;
use App\Models\Billing\Invoice;
use App\Services\Store\GatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * @OA\Tag(
 *     name="Customer Invoices",
 *     description="Invoice endpoints for customer API"
 * )
 */
class InvoiceController extends Controller
{
    /**
     * @OA\Get(
     *     path="/client/invoices",
     *     summary="List customer's invoices",
     *     tags={"Customer Invoices"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="filter",
     *         in="query",
     *         description="Filter by status (pending, paid, cancelled, refunded)",
     *         required=false,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of invoices"
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Invoice::where('customer_id', $request->user()->id)
            ->where('status', '!=', Invoice::STATUS_DRAFT)
            ->orderBy('created_at', 'desc');

        if ($request->has('filter') && in_array($request->filter, array_keys(Invoice::FILTERS))) {
            $query->where('status', $request->filter);
        }

        $invoices = $query->paginate($request->input('per_page', 10));

        return response()->json([
            'data' => $invoices->map(fn($invoice) => $this->formatInvoice($invoice)),
            'meta' => [
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'per_page' => $invoices->perPage(),
                'total' => $invoices->total(),
            ],
            'filters' => Invoice::FILTERS,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/client/invoices/{invoice}",
     *     summary="Get invoice details",
     *     tags={"Customer Invoices"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="invoice",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Invoice details with items"
     *     ),
     *     @OA\Response(response=404, description="Invoice not found")
     * )
     */
    public function show(Request $request, Invoice $invoice): JsonResponse
    {
        if ($invoice->customer_id !== $request->user()->id) {
            return response()->json(['error' => __('client.invoices.not_found')], 404);
        }

        if ($invoice->isDraft()) {
            return response()->json(['error' => __('client.invoices.not_found')], 404);
        }

        $gateways = GatewayService::getAvailable();

        return response()->json([
            'data' => $this->formatInvoice($invoice, true),
            'available_gateways' => collect($gateways)->map(fn($g) => [
                'uuid' => $g->uuid,
                'name' => $g->name,
                'minimal_amount' => $g->minimal_amount,
            ]),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/client/invoices/{invoice}/pdf",
     *     summary="Stream invoice PDF",
     *     tags={"Customer Invoices"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="invoice",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="PDF stream"
     *     )
     * )
     */
    public function pdf(Request $request, Invoice $invoice)
    {
        if ($invoice->customer_id !== $request->user()->id) {
            return response()->json(['error' => __('client.invoices.not_found')], 404);
        }

        return $invoice->pdf();
    }

    /**
     * @OA\Get(
     *     path="/client/invoices/{invoice}/download",
     *     summary="Download invoice PDF",
     *     tags={"Customer Invoices"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="invoice",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="PDF download"
     *     )
     * )
     */
    public function download(Request $request, Invoice $invoice)
    {
        if ($invoice->customer_id !== $request->user()->id) {
            return response()->json(['error' => __('client.invoices.not_found')], 404);
        }

        return $invoice->download();
    }

    /**
     * @OA\Post(
     *     path="/client/invoices/{invoice}/pay/{gateway}",
     *     summary="Pay an invoice",
     *     tags={"Customer Invoices"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="invoice",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(
     *         name="gateway",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Payment initiated",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="redirect_url", type="string"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(response=400, description="Cannot pay invoice")
     * )
     */
    public function pay(Request $request, Invoice $invoice, string $gateway): JsonResponse
    {
        if ($invoice->customer_id !== $request->user()->id) {
            return response()->json(['error' => __('client.invoices.not_found')], 404);
        }

        if ($invoice->total == 0) {
            $gatewayModel = Gateway::where('uuid', 'none')->first();
        } else {
            $gatewayModel = Gateway::getAvailable()->where('uuid', $gateway)->first();
            if ($gatewayModel === null) {
                return response()->json([
                    'error' => __('store.checkout.gateway_not_found'),
                ], 400);
            }
        }

        try {
            if ($gatewayModel->minimal_amount > $invoice->total) {
                return response()->json([
                    'error' => __('store.checkout.minimal_amount', ['amount' => formatted_price($gatewayModel->minimal_amount)]),
                ], 400);
            }

            if (! $invoice->canPay()) {
                return response()->json([
                    'error' => __('client.invoices.invoice_not_payable'),
                ], 400);
            }

            $result = $invoice->pay($gatewayModel, $request);

            // Check if result is a redirect response
            if ($result instanceof \Illuminate\Http\RedirectResponse) {
                return response()->json([
                    'redirect_url' => $result->getTargetUrl(),
                    'message' => __('store.checkout.redirecting'),
                ]);
            }

            return response()->json([
                'message' => __('client.invoices.payment_initiated'),
                'data' => $result,
            ]);
        } catch (WrongPaymentException $e) {
            logger()->error($e->getMessage());

            return response()->json([
                'error' => __('store.checkout.wrong_payment'),
            ], 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/client/invoices/{invoice}/balance",
     *     summary="Add balance to invoice",
     *     tags={"Customer Invoices"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="invoice",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"amount"},
     *
     *             @OA\Property(property="amount", type="number")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Balance added"
     *     ),
     *     @OA\Response(response=400, description="Insufficient balance")
     * )
     */
    public function balance(Request $request, Invoice $invoice): JsonResponse
    {
        if ($invoice->customer_id !== $request->user()->id) {
            return response()->json(['error' => __('client.invoices.not_found')], 404);
        }

        if (! setting('allow_add_balance_to_invoices')) {
            return response()->json(['error' => __('client.invoices.balance.disabled')], 403);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        $customer = $request->user();
        if ($validated['amount'] > $customer->balance) {
            return response()->json([
                'error' => __('client.invoices.balance.balance_not_enough'),
            ], 400);
        }

        if ($invoice->balance + $validated['amount'] > $invoice->total) {
            return response()->json([
                'error' => __('client.invoices.balance.exceeds_total'),
            ], 400);
        }

        $invoice->addBalance($validated['amount']);

        return response()->json([
            'message' => __('client.invoices.balance.success'),
            'data' => $this->formatInvoice($invoice->fresh(), true),
        ]);
    }

    private function formatInvoice(Invoice $invoice, bool $withItems = false): array
    {
        $data = [
            'id' => $invoice->id,
            'uuid' => $invoice->uuid,
            'external_id' => $invoice->external_id,
            'status' => $invoice->status,
            'status_label' => __('billing.invoices.status.' . $invoice->status),
            'subtotal' => $invoice->subtotal,
            'tax' => $invoice->tax,
            'total' => $invoice->total,
            'balance' => $invoice->balance,
            'formatted_subtotal' => formatted_price($invoice->subtotal, $invoice->currency),
            'formatted_tax' => formatted_price($invoice->tax, $invoice->currency),
            'formatted_total' => formatted_price($invoice->total, $invoice->currency),
            'formatted_balance' => formatted_price($invoice->balance, $invoice->currency),
            'currency' => $invoice->currency,
            'due_date' => $invoice->due_date?->toIso8601String(),
            'paid_at' => $invoice->paid_at?->toIso8601String(),
            'created_at' => $invoice->created_at->toIso8601String(),
            'can_pay' => $invoice->canPay(),
        ];

        if ($withItems) {
            $data['items'] = $invoice->items->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total' => $item->total,
                'formatted_unit_price' => formatted_price($item->unit_price, $invoice->currency),
                'formatted_total' => formatted_price($item->total, $invoice->currency),
            ]);

            $data['billing_address'] = $invoice->billing_address;
        }

        return $data;
    }
}
