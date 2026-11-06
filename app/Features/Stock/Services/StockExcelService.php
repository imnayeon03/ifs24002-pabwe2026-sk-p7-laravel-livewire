<?php

namespace App\Features\Stock\Services;

use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StockExcelService
{
    public function __construct(
        protected StockRepositoryInterface $stockRepository
    ) {}

    /**
     * Build a Spreadsheet instance containing stocks data.
     *
     * @param Collection<int, Stock> $stocks
     */
    public function buildSpreadsheet(Collection $stocks, string $sheetTitle = 'Stocks'): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($sheetTitle, 0, 31));

        $headers = ['No', 'Name', 'SKU', 'Quantity', 'Unit', 'Price', 'Min Stock', 'Status', 'Description', 'Created At'];
        $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];

        foreach ($headers as $index => $header) {
            $sheet->setCellValue("{$columns[$index]}1", $header);
        }

        // Header Styling
        $headerRange = 'A1:J1';
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('2563EB');
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $rowNumber = 2;
        foreach ($stocks as $idx => $stock) {
            $sheet->setCellValue("A{$rowNumber}", $idx + 1);
            $sheet->setCellValue("B{$rowNumber}", $stock->name);
            $sheet->setCellValue("C{$rowNumber}", $stock->sku ?? '-');
            $sheet->setCellValue("D{$rowNumber}", $stock->quantity);
            $sheet->setCellValue("E{$rowNumber}", $stock->unit ?? 'pcs');
            $sheet->setCellValue("F{$rowNumber}", $stock->price !== null ? (float) $stock->price : '-');
            $sheet->setCellValue("G{$rowNumber}", $stock->min_stock);
            $sheet->setCellValue("H{$rowNumber}", $stock->statusEnum()->label());
            $sheet->setCellValue("I{$rowNumber}", $stock->description ?? '-');
            $sheet->setCellValue("J{$rowNumber}", $stock->created_at ? $stock->created_at->format('Y-m-d H:i') : '-');
            $rowNumber++;
        }

        $lastRow = max(2, $rowNumber - 1);
        $sheet->getStyle("A1:J{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        foreach ($columns as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return $spreadsheet;
    }

    /**
     * Export stocks as a downloadable streamed response.
     *
     * @param Collection<int, Stock> $stocks
     */
    public function exportToResponse(Collection $stocks, string $filename = 'stock-inventory.xlsx'): StreamedResponse
    {
        $spreadsheet = $this->buildSpreadsheet($stocks);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Import stocks from an uploaded spreadsheet file path.
     *
     * @return array{imported: int, errors: array<int, string>}
     */
    public function import(string $filePath, int $userId): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        $imported = 0;
        $errors = [];
        $recordsToInsert = [];
        $now = Carbon::now();

        // Skip header row
        $isFirst = true;
        $rowIndex = 1;

        foreach ($rows as $row) {
            if ($isFirst) {
                $isFirst = false;
                $rowIndex++;
                continue;
            }

            // Detect if column A is number / "No" or Name directly
            $colA = isset($row['A']) ? trim((string) $row['A']) : '';
            $colB = isset($row['B']) ? trim((string) $row['B']) : '';

            $name = '';
            $sku = null;
            $quantity = 0;
            $unit = 'pcs';
            $price = null;
            $minStock = 5;
            $statusRaw = '';
            $description = null;

            if (is_numeric($colA) && $colB !== '') {
                // Header format: No | Name | SKU | Quantity | Unit | Price | Min Stock | Status | Description
                $name = $colB;
                $sku = isset($row['C']) && trim((string) $row['C']) !== '-' ? trim((string) $row['C']) : null;
                $quantity = isset($row['D']) && is_numeric($row['D']) ? (int) $row['D'] : 0;
                $unit = isset($row['E']) && trim((string) $row['E']) !== '' ? trim((string) $row['E']) : 'pcs';
                $price = isset($row['F']) && is_numeric($row['F']) ? (float) $row['F'] : null;
                $minStock = isset($row['G']) && is_numeric($row['G']) ? (int) $row['G'] : 5;
                $statusRaw = isset($row['H']) ? strtolower(trim((string) $row['H'])) : '';
                $description = isset($row['I']) && trim((string) $row['I']) !== '-' ? trim((string) $row['I']) : null;
            } else {
                // Header format starting with Name at col A
                $name = $colA;
                $sku = isset($row['B']) && trim((string) $row['B']) !== '-' ? trim((string) $row['B']) : null;
                $quantity = isset($row['C']) && is_numeric($row['C']) ? (int) $row['C'] : 0;
                $unit = isset($row['D']) && trim((string) $row['D']) !== '' ? trim((string) $row['D']) : 'pcs';
                $price = isset($row['E']) && is_numeric($row['E']) ? (float) $row['E'] : null;
                $minStock = isset($row['F']) && is_numeric($row['F']) ? (int) $row['F'] : 5;
                $statusRaw = isset($row['G']) ? strtolower(trim((string) $row['G'])) : '';
                $description = isset($row['H']) && trim((string) $row['H']) !== '-' ? trim((string) $row['H']) : null;
            }

            if ($name === '' || strtolower($name) === 'no' || strtolower($name) === 'name' || strtolower($name) === 'nama') {
                $rowIndex++;
                continue;
            }

            $validStatuses = [
                StockStatus::AVAILABLE->value,
                StockStatus::LOW_STOCK->value,
                StockStatus::OUT_OF_STOCK->value,
            ];

            // Normalize label to enum value if needed
            if ($statusRaw === 'tersedia') {
                $statusRaw = StockStatus::AVAILABLE->value;
            } elseif ($statusRaw === 'stok menipis') {
                $statusRaw = StockStatus::LOW_STOCK->value;
            } elseif ($statusRaw === 'habis') {
                $statusRaw = StockStatus::OUT_OF_STOCK->value;
            }

            if (in_array($statusRaw, $validStatuses, true)) {
                $status = $statusRaw;
            } else {
                $status = $quantity <= 0 ? StockStatus::OUT_OF_STOCK->value : ($quantity <= $minStock ? StockStatus::LOW_STOCK->value : StockStatus::AVAILABLE->value);
            }

            $recordsToInsert[] = [
                'user_id' => $userId,
                'name' => substr($name, 0, 255),
                'sku' => $sku ? substr($sku, 0, 100) : null,
                'description' => $description,
                'quantity' => max(0, $quantity),
                'unit' => substr($unit, 0, 50),
                'price' => $price,
                'min_stock' => max(0, $minStock),
                'status' => $status,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $imported++;
            $rowIndex++;
        }

        if (!empty($recordsToInsert)) {
            $this->stockRepository->insertMany($recordsToInsert);
        }

        return [
            'imported' => $imported,
            'errors' => $errors,
        ];
    }
}
