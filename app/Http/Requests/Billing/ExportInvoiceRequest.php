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
use App\Services\InvoiceExporterService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

/**
 * @OA\Schema(
 *     title="ExportInvoiceRequest",
 *     description="Request body for exporting invoices",
 *     @OA\Property(
 *         property="format",
 *         type="string",
 *         enum={"pdf", "csv", "xlsx"},
 *         description="The format to export the invoices"
 *     ),
 *     @OA\Property(
 *         property="date_from",
 *         type="string",
 *         format="date",
 *         description="The start date for the invoice export"
 *     ),
 *     @OA\Property(
 *         property="date_to",
 *         type="string",
 *         format="date",
 *         description="The end date for the invoice export"
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="array",
 *         @OA\Items(type="string", enum={"paid", "unpaid", "cancelled"}),
 *         description="The status of the invoices to export"
 *     )
 * )
 */
class ExportInvoiceRequest extends FormRequest
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
            'format' => 'required|string|in:'.join(',', array_keys(InvoiceExporterService::getAvailableFormats())),
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'status' => 'nullable|array|in:'.implode(',', array_keys($this->getIndexFilters())),
        ];
    }


    private function getIndexFilters()
    {
        return Invoice::FILTERS + [Invoice::STATUS_DRAFT => Invoice::STATUS_DRAFT];
    }

    public function export(): \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\RedirectResponse
    {
        $validatedData = $this->validated();
        $invoices = Invoice::query()
            ->when($validatedData['date_from'], function ($query) use ($validatedData) {
                return $query->where('created_at', '>=', $validatedData['date_from']);
            })
            ->when($validatedData['date_to'], function ($query) use ($validatedData) {
                return $query->where('created_at', '<=', $validatedData['date_to']);
            })
            ->when($validatedData['status'], function ($query) use ($validatedData) {
                if (in_array('all', $validatedData['status'])) {
                    return $query;
                }
                return $query->whereIn('status', $validatedData['status']);
            })
            ->get();
        if ($invoices->isEmpty()) {
            return back()->with('error', __('global.no_results'));
        }
        $filePath = InvoiceExporterService::exportInvoices($invoices, $validatedData['format']);

        return response()->download($filePath)->deleteFileAfterSend(true);

    }
}
