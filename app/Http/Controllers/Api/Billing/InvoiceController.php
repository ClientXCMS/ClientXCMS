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

namespace App\Http\Controllers\Api\Billing;

use App\Events\Core\Invoice\InvoiceCreated;
use App\Http\Controllers\Api\AbstractApiController;
use App\Http\Requests\Billing\ExportInvoiceRequest;
use App\Http\Requests\Billing\StoreInvoiceRequest;
use App\Http\Requests\Billing\UpdateInvoiceRequest;
use App\Http\Resources\Billing\InvoiceCollection;
use App\Models\Billing\Invoice;
use App\Services\Billing\InvoiceService;
use Illuminate\Http\Request;

class InvoiceController extends AbstractApiController
{
    protected string $model = Invoice::class;

    protected array $sorts = [
        'id',
        'customer_id',
        'status',
        'amount',
        'created_at',
        'updated_at',
    ];

    protected array $relations = [
        'customer',
        'items',
    ];

    protected array $filters = [
        'id',
        'customer_id',
        'status',
        'amount',
        'created_at',
        'updated_at',
    ];
    /**
     * @OA\Get(
     *     path="/application/invoices",
     *     summary="Get a list of invoices",
     *     tags={"Invoices"},
     *     @OA\Response(
     *         response=200,
     *         description="A list of invoices",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Invoice"))
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Sort order",
     *         required=false,
     *         @OA\Schema(type="string", default="created_at")
     *     ),
     *     @OA\Parameter(
     *         name="filter",
     *         in="query",
     *         description="Filter invoices",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="include",
     *         in="query",
     *         description="Related resources to include",
     *         required=false,
     *         @OA\Schema(type="string", default="customer,items")
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = $this->queryIndex($request);
        return new InvoiceCollection($query);
    }
    /**
     * @OA\Post(
     *     path="/application/invoices",
     *     summary="Create a new invoice",
     *     tags={"Invoices"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/StoreInvoiceRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Invoice created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Invoice")
     *     )
     * )
     */
    public function store(StoreInvoiceRequest $request)
    {
        $validatedData = $request->validated();
        $invoice = InvoiceService::createFreshInvoice($validatedData['customer_id'], $validatedData['currency'], 'Created manually by ' . (auth('admin')->user()->username ?? 'API'));
        return response()->json($invoice, 201);
    }

    /**
     * @OA\Get(
     *     path="/application/invoices/{invoice}",
     *     summary="Get a single invoice",
     *     tags={"Invoices"},
     *     @OA\Response(
     *         response=200,
     *         description="A single invoice",
     *         @OA\JsonContent(ref="#/components/schemas/Invoice")
     *     ),
     *     @OA\Parameter(
     *         name="invoice",
     *         in="path",
     *         description="ID or UUID of the invoice",
     *         required=true,
     *         @OA\Schema(type="string")
     *     )
     * )
     */
    public function show(int $invoice)
    {
        return $this->queryShow($invoice);
    }
    /**
     * @OA\Put(
     *     path="/application/invoices/{invoice}",
     *     summary="Update an existing invoice",
     *     tags={"Invoices"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UpdateInvoiceRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Invoice updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Invoice")
     *     )
     * )
     */
    public function update(UpdateInvoiceRequest $request, Invoice $invoice)
    {
        $request->update($invoice);
        return response()->json($invoice, 200);
    }
    /**
     * @OA\Delete(
     *     path="/application/invoices/{invoice}",
     *     summary="Delete an existing invoice",
     *     tags={"Invoices"},
     *     @OA\Response(
     *         response=204,
     *         description="Invoice deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Invoice cannot be deleted"
     *     ),
     *     @OA\Parameter(
     *         name="invoice",
     *         in="path",
     *         description="ID or UUID of the invoice",
     *         required=true,
     *         @OA\Schema(type="string")
     *     )
     * )
     */
    public function destroy(Invoice $invoice)
    {
        if ($invoice->canDelete()) {
            $invoice->delete();
            return response()->json(['message' => 'Invoice deleted successfully'], 204);
        } else {
            return response()->json(['message' => 'Invoice cannot be deleted'], 403);
        }
    }

    /**
     * @OA\Get(
     *     path="/application/invoices/{invoice}/pdf",
     *     summary="Get the PDF of a single invoice",
     *     tags={"Invoices"},
     *     @OA\Response(
     *         response=200,
     *         description="PDF of the invoice",
     *         @OA\JsonContent(ref="#/components/schemas/Invoice")
     *     ),
     *     @OA\Parameter(
     *         name="invoice",
     *         in="path",
     *         description="ID or UUID of the invoice",
     *         required=true,
     *         @OA\Schema(type="string")
     *     )
     * )
     */
    public function pdf(Request $request, Invoice $invoice)
    {
        return $invoice->pdf();
    }

    /**
     * @OA\Post(
     *     path="/application/invoices/export",
     *     summary="Get the Excel of a single invoice",
     *     tags={"Invoices"},
     *     @OA\Response(
     *         response=200,
     *         description="Excel of the invoice",
     *         @OA\JsonContent(ref="#/components/schemas/Invoice")
     *     ),
     *     @OA\Parameter(
     *         name="invoice",
     *         in="path",
     *         description="ID or UUID of the invoice",
     *         required=true,
     *         @OA\Schema(type="string")
     *     )
     * )
     */
    public function excel(ExportInvoiceRequest $request, Invoice $invoice)
    {
        return $request->export();
    }

    /**
     * @OA\Post(
     *     path="/application/invoices/{invoice}/validate",
     *     summary="Validate an invoice",
     *     tags={"Invoices"},
     *     @OA\Response(
     *         response=200,
     *         description="Invoice validated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Invoice")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation errors",
     *     ),
     *     @OA\Parameter(
     *         name="invoice",
     *         in="path",
     *         description="ID or UUID of the invoice",
     *         required=true,
     *         @OA\Schema(type="string")
     *     )
     * )
     */
    public function validate(Invoice $invoice)
    {
        if ($invoice->status != Invoice::STATUS_DRAFT) {
            return response()->json([
                'message' => __('admin.invoices.draft.not_in_draft'),
            ], 400);
        }
        if ($invoice->items->count() == 0) {
            return response()->json([
                'message' => __('admin.invoices.draft.empty'),
            ], 400);
        }
        $invoice->status = Invoice::STATUS_PENDING;
        $invoice->save();
        event(new InvoiceCreated($invoice));
        return response()->json([
            'message' => __('admin.invoices.draft.validated'),
            'invoice' => $invoice,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/application/invoices/{invoice}/edit",
     *     summary="Edit an invoice",
     *     tags={"Invoices"},
     *     @OA\Response(
     *         response=200,
     *         description="Invoice edited successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Invoice")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation errors"
     *     ),
     *     @OA\Parameter(
     *         name="invoice",
     *         in="path",
     *         description="ID or UUID of the invoice",
     *         required=true,
     *         @OA\Schema(type="string")
     *     )
     * )
     */
    public function edit(Invoice $invoice)
    {
        if ($invoice->status != Invoice::STATUS_PENDING) {
            return response()->json([
                'message' => __('admin.invoices.edit.not_pending'),
            ], 400);
        }
        $invoice->status = Invoice::STATUS_DRAFT;
        $invoice->save();
        return response()->json([
            'message' => __('admin.invoices.draft.set_in_draft'),
            'invoice' => $invoice,
        ], 200);
    }
    /**
     * @OA\Get(
     *     path="/application/invoices/{invoice}/notify",
     *     summary="Notify about an invoice",
     *     tags={"Invoices"},
     *     @OA\Response(
     *         response=200,
     *         description="Notification sent successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Invoice")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation errors",
     *     ),
     *     @OA\Parameter(
     *         name="invoice",
     *         in="path",
     *         description="ID or UUID of the invoice",
     *         required=true,
     *         @OA\Schema(type="string")
     *     )
     * )
     */
    public function notify(Invoice $invoice)
    {
        if ($invoice->status != Invoice::STATUS_PENDING) {
            return response()->json([
                'message' => __('admin.invoices.notify.not_pending'),
            ], 400);
        }
        $invoice->notifyCustomer();
        return response()->json([
            'message' => __('admin.invoices.notify.sent'),
            'invoice' => $invoice,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/application/invoices/{invoice}/download",
     *     summary="Download an invoice as PDF",
     *     tags={"Invoices"},
     *     @OA\Response(
     *         response=200,
     *         description="PDF file download"
     *     ),
     *     @OA\Parameter(
     *         name="invoice",
     *         in="path",
     *         description="ID or UUID of the invoice",
     *         required=true,
     *         @OA\Schema(type="string")
     *     )
     * )
     */
    public function download(Invoice $invoice)
    {
        return $invoice->download();
    }
}
