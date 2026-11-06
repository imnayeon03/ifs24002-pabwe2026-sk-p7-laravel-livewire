<?php

namespace Tests\Unit\Services;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Features\Stock\Repositories\StockRepository;
use App\Features\Stock\Services\StockExcelService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class StockExcelServiceTest extends TestCase
{
    use RefreshDatabase;

    protected StockExcelService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StockExcelService(new StockRepository());
    }

    public function test_build_spreadsheet_and_export_response(): void
    {
        $user = User::factory()->create();
        $stocks = Stock::factory()->count(2)->create(['user_id' => $user->id]);

        $spreadsheet = $this->service->buildSpreadsheet($stocks, 'Custom Stocks');
        $this->assertInstanceOf(Spreadsheet::class, $spreadsheet);
        $this->assertEquals('Custom Stocks', $spreadsheet->getActiveSheet()->getTitle());

        $response = $this->service->exportToResponse($stocks, 'test-stocks.xlsx');
        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        ob_start();
        $response->sendContent();
        $outputContent = ob_get_clean();
        $this->assertNotEmpty($outputContent);
    }

    public function test_import_spreadsheet_with_standard_columns(): void
    {
        $user = User::factory()->create();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $sheet->setCellValue('A1', 'No');
        $sheet->setCellValue('B1', 'Name');
        $sheet->setCellValue('C1', 'SKU');
        $sheet->setCellValue('D1', 'Quantity');
        $sheet->setCellValue('E1', 'Unit');
        $sheet->setCellValue('F1', 'Price');
        $sheet->setCellValue('G1', 'Min Stock');
        $sheet->setCellValue('H1', 'Status');
        $sheet->setCellValue('I1', 'Description');

        // Row 2: Standard available stock
        $sheet->setCellValue('A2', '1');
        $sheet->setCellValue('B2', 'Beras Ramos 5kg');
        $sheet->setCellValue('C2', 'BRS-001');
        $sheet->setCellValue('D2', '25');
        $sheet->setCellValue('E2', 'sak');
        $sheet->setCellValue('F2', '75000');
        $sheet->setCellValue('G2', '5');
        $sheet->setCellValue('H2', 'available');
        $sheet->setCellValue('I2', 'Beras pulen premium');

        // Row 3: Indonesian label 'stok menipis'
        $sheet->setCellValue('A3', '2');
        $sheet->setCellValue('B3', 'Minyak Goreng 2L');
        $sheet->setCellValue('C3', 'MYK-002');
        $sheet->setCellValue('D3', '3');
        $sheet->setCellValue('E3', 'pouch');
        $sheet->setCellValue('F3', '34000');
        $sheet->setCellValue('G3', '5');
        $sheet->setCellValue('H3', 'stok menipis');
        $sheet->setCellValue('I3', '-');

        // Row 4: Indonesian label 'habis', empty unit, invalid price
        $sheet->setCellValue('A4', '3');
        $sheet->setCellValue('B4', 'Tepung Terigu 1kg');
        $sheet->setCellValue('C4', '-');
        $sheet->setCellValue('D4', '0');
        $sheet->setCellValue('E4', '');
        $sheet->setCellValue('F4', 'invalid');
        $sheet->setCellValue('G4', '10');
        $sheet->setCellValue('H4', 'habis');
        $sheet->setCellValue('I4', 'Tepung serbaguna');

        // Row 5: Indonesian label 'tersedia', unknown status fallback
        $sheet->setCellValue('A5', '4');
        $sheet->setCellValue('B5', 'Gula Pasir 1kg');
        $sheet->setCellValue('C5', 'GLA-003');
        $sheet->setCellValue('D5', '50');
        $sheet->setCellValue('E5', 'kg');
        $sheet->setCellValue('F5', '18000');
        $sheet->setCellValue('G5', '10');
        $sheet->setCellValue('H5', 'tersedia');
        $sheet->setCellValue('I5', 'Gula tebu murni');

        // Row 6: Unknown status fallback (auto calculated based on quantity & min_stock)
        $sheet->setCellValue('A6', '5');
        $sheet->setCellValue('B6', 'Kecap Manis');
        $sheet->setCellValue('C6', 'KCP-004');
        $sheet->setCellValue('D6', '2');
        $sheet->setCellValue('E6', 'botol');
        $sheet->setCellValue('F6', '12000');
        $sheet->setCellValue('G6', '5');
        $sheet->setCellValue('H6', 'unknown_status');
        $sheet->setCellValue('I6', 'Kecap botol');

        // Row 7: Duplicate header / empty name skip
        $sheet->setCellValue('A7', 'No');
        $sheet->setCellValue('B7', 'Name');
        $sheet->setCellValue('C7', 'SKU');

        $tempPath = tempnam(sys_get_temp_dir(), 'test_stock_excel_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        $result = $this->service->import($tempPath, $user->id);
        @unlink($tempPath);

        $this->assertEquals(5, $result['imported']);

        $this->assertDatabaseHas('stocks', [
            'user_id' => $user->id,
            'name' => 'Beras Ramos 5kg',
            'sku' => 'BRS-001',
            'quantity' => 25,
            'unit' => 'sak',
            'status' => StockStatus::AVAILABLE->value,
        ]);

        $this->assertDatabaseHas('stocks', [
            'user_id' => $user->id,
            'name' => 'Minyak Goreng 2L',
            'sku' => 'MYK-002',
            'quantity' => 3,
            'description' => null,
            'status' => StockStatus::LOW_STOCK->value,
        ]);

        $this->assertDatabaseHas('stocks', [
            'user_id' => $user->id,
            'name' => 'Tepung Terigu 1kg',
            'sku' => null,
            'quantity' => 0,
            'unit' => 'pcs',
            'price' => null,
            'status' => StockStatus::OUT_OF_STOCK->value,
        ]);

        $this->assertDatabaseHas('stocks', [
            'user_id' => $user->id,
            'name' => 'Gula Pasir 1kg',
            'status' => StockStatus::AVAILABLE->value,
        ]);

        $this->assertDatabaseHas('stocks', [
            'user_id' => $user->id,
            'name' => 'Kecap Manis',
            'status' => StockStatus::LOW_STOCK->value, // qty 2 <= min 5
        ]);
    }

    public function test_import_spreadsheet_starting_without_no_column(): void
    {
        $user = User::factory()->create();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header starts with Name
        $sheet->setCellValue('A1', 'Name');
        $sheet->setCellValue('B1', 'SKU');
        $sheet->setCellValue('C1', 'Quantity');
        $sheet->setCellValue('D1', 'Unit');
        $sheet->setCellValue('E1', 'Price');
        $sheet->setCellValue('F1', 'Min Stock');
        $sheet->setCellValue('G1', 'Status');
        $sheet->setCellValue('H1', 'Description');

        // Row 2: Name in col A
        $sheet->setCellValue('A2', 'Kopi Robusta 250g');
        $sheet->setCellValue('B2', 'KOP-250');
        $sheet->setCellValue('C2', '0');
        $sheet->setCellValue('D2', 'pack');
        $sheet->setCellValue('E2', '25000');
        $sheet->setCellValue('F2', '5');
        $sheet->setCellValue('G2', 'random_status'); // auto fallback to out_of_stock because qty 0
        $sheet->setCellValue('H2', 'Kopi bubuk robusta');

        $tempPath = tempnam(sys_get_temp_dir(), 'test_stock_excel2_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        $result = $this->service->import($tempPath, $user->id);
        @unlink($tempPath);

        $this->assertEquals(1, $result['imported']);
        $this->assertDatabaseHas('stocks', [
            'user_id' => $user->id,
            'name' => 'Kopi Robusta 250g',
            'sku' => 'KOP-250',
            'quantity' => 0,
            'status' => StockStatus::OUT_OF_STOCK->value,
        ]);
    }

    public function test_import_empty_spreadsheet(): void
    {
        $user = User::factory()->create();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Name');

        $tempPath = tempnam(sys_get_temp_dir(), 'test_stock_excel3_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        $result = $this->service->import($tempPath, $user->id);
        @unlink($tempPath);

        $this->assertEquals(0, $result['imported']);
    }
}
