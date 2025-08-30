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
namespace App\Http\Requests\Billing;

use App\Models\Billing\Invoice;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     title="UpdateInvoiceRequest",
 *     description="Request body for updating invoices",
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         enum={"draft", "sent", "paid", "cancelled"},
 *         description="The status of the invoice"
 *     ),
 *     @OA\Property(
 *         property="notes",
 *         type="string",
 *         description="Additional notes for the invoice"
 *     ),
 *     @OA\Property(
 *         property="paymethod",
 *         type="string",
 *         description="The payment method used for the invoice"
 *     ),
 *     @OA\Property(
 *         property="fees",
 *         type="number",
 *         format="float",
 *         description="Any additional fees for the invoice"
 *     ),
 *     @OA\Property(
 *         property="tax",
 *         type="number",
 *         format="float",
 *         description="The tax amount for the invoice"
 *     ),
 *     @OA\Property(
 *         property="currency",
 *         type="string",
 *         description="The currency of the invoice"
 *     ),
 *     @OA\Property(
 *         property="due_date",
 *         type="string",
 *         format="date",
 *         description="The due date for the invoice"
 *     ),
 *     @OA\Property(
 *         property="paid_at",
 *         type="string",
 *         format="date",
 *         description="The date the invoice was paid"
 *     ),
 *     @OA\Property(
 *         property="balance",
 *         type="number",
 *         format="float",
 *         description="The remaining balance for the invoice"
 *     ),
 *     @OA\Property(
 *         property="payment_method_id",
 *         type="integer",
 *         description="The ID of the payment method used"
 *     ),
 *     @OA\Property(
 *         property="billing_address",
 *         type="array",
 *         @OA\Items(type="string"),
 *         description="The billing address for the invoice"
 *     ),
 *     @OA\Property(
 *         property="external_id",
 *         type="string",
 *         description="An external ID for the invoice"
 *     )
 * )
 */
class UpdateInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => 'required|in:'.implode(',', array_keys(Invoice::getStatuses())),
            'notes' => 'required_if:billing_address,null|string|max:255',
            'paymethod' => 'required_if:billing_address,null|string|max:255',
            'fees' => 'required_if:billing_address,null|numeric|min:0',
            'tax' => 'required_if:billing_address,null|numeric|min:0',
            'currency' => 'required_if:billing_address,null|string|max:3',
            'due_date' => 'required_if:billing_address,null|date',
            'paid_at' => 'nullable|date',
            'balance' => 'nullable|numeric|min:0|max:' . $this->invoice->total,
            'payment_method_id' => 'nullable',
            'billing_address' => 'nullable|array',
            'external_id' => ['nullable', 'string', 'max:255', 'unique:invoices,external_id,'.$this->invoice->id],
        ];
    }

    public function update(Invoice $invoice)
    {
        $validatedData = $this->validated();
        if ($validatedData['status'] != $invoice->status) {
            if ($validatedData['status'] == Invoice::STATUS_PAID) {
                $invoice->complete(false);
            }
            if ($validatedData['status'] == Invoice::STATUS_CANCELLED) {
                $invoice->cancel();
            }
            if ($validatedData['status'] == Invoice::STATUS_REFUNDED) {
                $invoice->refund();
            }
            if ($validatedData['status'] == Invoice::STATUS_FAILED) {
                $invoice->fail();
            }
        }
        $invoice->update($validatedData);
        $invoice->recalculate();
    }
}
