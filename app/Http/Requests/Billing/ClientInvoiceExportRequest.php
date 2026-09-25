<?php

namespace App\Http\Requests\Billing;

use App\Models\Billing\Invoice;
use App\Services\InvoiceExporterService;
use Illuminate\Foundation\Http\FormRequest;

class ClientInvoiceExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'format' => 'required|string|in:'.implode(',', array_keys(InvoiceExporterService::getAvailableFormats())),
            'date_from' => 'nullable|date|before_or_equal:date_to',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'status' => 'nullable|array',
            'status.*' => 'string|in:'.implode(',', array_keys(Invoice::FILTERS)),
            'currency' => 'nullable|string|size:3',
        ];
    }
}
