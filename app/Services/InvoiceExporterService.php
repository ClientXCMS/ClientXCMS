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

namespace App\Services;

use App\Models\Billing\Invoice;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Facades\Excel;

class InvoiceExporterService
{
    public static function getAvailableFormats(): array
    {
        return [
            'csv' => 'CSV',
            'xlsx' => 'Excel',
            'pdf' => 'Zip PDF',
        ];
    }

    /**
     * Exports invoices to the specified format.
     *
     * @param  Collection<Invoice>  $invoices
     *
     * @throws \InvalidArgumentException
     */
    public static function exportInvoices(Collection $invoices, string $format): string
    {
        switch ($format) {
            case 'csv':
                return self::exportToCsv($invoices);
            case 'xlsx':
                return self::exportToXlsx($invoices);
            case 'pdf':
                return self::exportToPdf($invoices);
            default:
                throw new \InvalidArgumentException("Unsupported format: $format");
        }
    }

    /**
     * @param  Collection<Invoice>  $invoices
     */
    private static function exportToCsv(Collection $invoices): string
    {
        $filename = storage_path('app/exports/invoices-'.date('Y-m-d_H-i-s').'.csv');
        if (! is_dir(dirname($filename))) {
            mkdir(dirname($filename), 0755, true);
        }
        $file = fopen($filename, 'w');
        fputcsv($file, self::getHeaderRow());
        foreach ($invoices as $invoice) {
            fputcsv($file, [
                $invoice->id,
                $invoice->customer ? $invoice->customer->fullName : 'Unknown',
                implode(' ', $invoice->billing_address),
                $invoice->due_date,
                $invoice->total,
                $invoice->tax,
                $invoice->setupfees,
                $invoice->currency,
                $invoice->status,
                $invoice->external_id,
                $invoice->notes,
                $invoice->gateway ? $invoice->gateway->name : 'Unknown',
                $invoice->fees,
                $invoice->invoice_number,
                $invoice->paid_at ? $invoice->paid_at->format('Y-m-d H:i:s') : null,
                $invoice->uuid,
                $invoice->payment_method_id,
                $invoice->balance,
            ]);
        }
        fclose($file);

        return $filename;
    }

    private static function exportToXlsx(Collection $invoices): string
    {
        $filename = 'exports/invoices-'.date('Y-m-d_H-i-s').'.xlsx';
        if (! is_dir(dirname($filename))) {
            mkdir(dirname($filename), 0755, true);
        }
        $data = [];
        $data[] = self::getHeaderRow();
        foreach ($invoices as $invoice) {
            $data[] = [
                $invoice->id,
                $invoice->customer ? $invoice->customer->fullName : 'Unknown',
                implode(' ', $invoice->billing_address),
                $invoice->due_date,
                $invoice->total,
                $invoice->tax,
                $invoice->setupfees,
                $invoice->currency,
                $invoice->status,
                $invoice->external_id,
                $invoice->notes,
                $invoice->gateway ? $invoice->gateway->name : 'Unknown',
                $invoice->fees,
                $invoice->invoice_number,
                $invoice->paid_at ? $invoice->paid_at->format('Y-m-d H:i:s') : null,
                $invoice->uuid,
                $invoice->payment_method_id,
                $invoice->balance,
            ];
        }

        Excel::store(new class($data) implements \Maatwebsite\Excel\Concerns\FromArray
        {
            private $data;

            public function __construct(array $data)
            {
                $this->data = $data;
            }

            public function array(): array
            {
                return $this->data;
            }
        }, $filename, 'local');

        return storage_path('app/'.$filename);
    }

    /**
     * @param  Collection<Invoice>  $invoices
     */
    private static function exportToPdf(Collection $invoices): string
    {
        $tempDir = storage_path('app/exports/invoices-pdf-'.date('Y-m-d_H-i-s'));
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        $pdfFiles = [];
        foreach ($invoices as $invoice) {
            $pdfFile = $tempDir.'/'.str_replace('/', '-', $invoice->getPdfName());
            $storagePath = storage_path('app/invoices/'.$invoice->getPdfName());
            if (! file_exists($storagePath)) {
                $invoice->generatePdf();
            }
            $pdfFiles[] = $pdfFile;
            copy($storagePath, $pdfFile);
        }
        $zipFile = storage_path('app/exports/invoices-'.date('Y-m-d_H-i-s').'.zip');
        $zip = new \ZipArchive;
        if ($zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Could not open zip file: $zipFile");
        }
        foreach ($invoices as $invoice) {
            $pdfFile = $tempDir.'/'.str_replace('/', '-', $invoice->getPdfName());
            if (file_exists($pdfFile)) {
                $zip->addFile($pdfFile, basename($pdfFile));
            }
        }
        $zip->close();
        // clean up temporary directory
        foreach ($pdfFiles as $pdfFile) {
            if (file_exists($pdfFile)) {
                unlink($pdfFile);
            }
        }
        if (is_dir($tempDir)) {
            rmdir($tempDir);
        }

        return $zipFile;
    }

    private static function getHeaderRow(): array
    {
        return [
            'ID',
            'Client',
            'Billing Address',
            'Due Date',
            'Total',
            'Tax',
            'Setup Fees',
            'Currency',
            'Status',
            'External ID',
            'Notes',
            'Payment Method',
            'Fees',
            'Invoice Number',
            'Paid At',
            'UUID',
            'Payment Method ID',
            'Balance',
        ];
    }
}
