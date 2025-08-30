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
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Store\Group;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;


class ExportPricingCommand extends Command
{
    protected $signature = 'pricing:export {filename? : output filename (default: CLIENTXCMS_pricing_YYYYMMDD_HHMMSS.xlsx)}';
    protected $description = 'Export all pricing data into an Excel file, grouped by product groups.';

    public function handle(): int
    {
        $filename = $this->argument('filename')
            ?? 'CLIENTXCMS_pricing_' . now()->format('Ymd_His') . '.xlsx';

        $spreadsheet = new Spreadsheet();
        $summary = $spreadsheet->getActiveSheet();
        $summary->setTitle('Groupes');
        $summary->setCellValue('A1', 'CLIENTXCMS Pricing – Récapitulatif');
        $summary->mergeCells('A1:G1');
        $summary->getStyle('A1')->getFont()->setBold(true)->setSize(16);

        $summary->fromArray(
            ['ID', 'Nom', 'Premier prix', 'Périodicité', 'Devise', 'Statut', 'Caché ?'],
            null,
            'A3'
        );
        $summary->getStyle('A3:G3')->applyFromArray($this->headerStyle());
        $rowSummary = 4;
        Group::with(['products.pricing', 'groups.products.pricing'])
             ->whereNull('parent_id')
            ->chunk(100, function ($parents) use (&$rowSummary, $summary, $spreadsheet) {

                foreach ($parents as $group) {
                    $sheet = $spreadsheet->createSheet();
                    $sheet->setTitle(Str::limit($group->trans('name'), 28));
                    $sheet->setCellValue('A1', "CLIENTXCMS Pricing – {$group->trans('name')}");
                    $sheet->mergeCells('A1:G1');
                    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

                    $sheet->fromArray(
                        ['ID', 'Produit', 'Type', 'Statut', 'Période', 'Prix', 'Setup'],
                        null,
                        'A3'
                    );
                    $sheet->getStyle('A3:G3')->applyFromArray($this->headerStyle());

                    $row   = 4;
                    $zebra = false;
                    $row = $this->dumpProducts($sheet, $group, $row, $zebra);
                    foreach ($group->groups as $child) {
                        $sheet->setCellValue("A{$row}", "Sous-groupe : {$child->trans('name')} (#{$child->id})");
                        $sheet->mergeCells("A{$row}:G{$row}");
                        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
                        $row++;
                        $row = $this->dumpProducts($sheet, $child, $row, $zebra);
                    }
                    foreach (range('A', 'G') as $col) {
                        $sheet->getColumnDimension($col)->setAutoSize(true);
                    }
                    $sheet->freezePane('A4');
                    $priceDTO = $group->startPrice();
                    $summary->fromArray(
                        [
                            $group->id,
                            $group->trans('name'),
                            $priceDTO->price,
                            $priceDTO->recurring,
                            $priceDTO->currency,
                            $group->status,
                            $group->status === 'hidden' ? 'Oui' : 'Non',
                        ],
                        null,
                        'A'.$rowSummary
                    );
                    $rowSummary++;
                }
            });

        foreach (range('A', 'G') as $col) {
            $summary->getColumnDimension($col)->setAutoSize(true);
        }
        $summary->freezePane('A4');
        $summary->getAutoFilter()->setRange('A3:G' . ($rowSummary - 1));
        $writer = new Xlsx($spreadsheet);
        $path   = storage_path('app/' . $filename);
        $writer->save($path);

        $this->info('File exported successfully to: ' . $path);
        return self::SUCCESS;
    }

    private function headerStyle(): array
    {
        return [
            'font' => ['bold' => true],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D9E1F2'],
            ],
            'borders' => [
                'bottom' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['argb' => '000000'],
                ],
            ],
        ];
    }

    private function dumpProducts($sheet, \App\Models\Store\Group $g, int $row, bool &$zebra): int
    {
        foreach ($g->products as $product) {
            foreach ($product->pricingAvailable() as $dto) {
                $sheet->fromArray(
                    [
                        $product->id,
                        $product->trans('name'),
                        $product->type ?? '-',
                        $product->status,
                        $dto->recurring,
                        $dto->price,
                        $dto->setup ?? '',
                    ],
                    null,
                    'A'.$row
                );

                if ($zebra) {
                    $sheet->getStyle("A{$row}:G{$row}")
                        ->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('F2F6FC');
                }
                $zebra = !$zebra;
                $row++;
            }
        }
        return $row + 1;
    }
}
