<?php

namespace Tests\Feature\Stock;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedStockItem;
use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Livewire\StockList;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class StockListLivewireTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_stocks(): void
    {
        $this->get('/stocks')->assertRedirect(route('login'));
    }

    public function test_member_can_view_own_stocks(): void
    {
        $user = User::factory()->member()->create();
        Stock::factory()->create(['user_id' => $user->id, 'name' => 'Barang Khusus Member']);

        $otherUser = User::factory()->member()->create();
        Stock::factory()->create(['user_id' => $otherUser->id, 'name' => 'Barang Rahasia Orang Lain']);

        $this->actingAs($user);

        Livewire::test(StockList::class)
            ->assertSee('Barang Khusus Member')
            ->assertDontSee('Barang Rahasia Orang Lain');
    }

    public function test_create_stock_validation_and_success(): void
    {
        $user = User::factory()->member()->create();
        $this->actingAs($user);

        // Validation error
        Livewire::test(StockList::class)
            ->call('openCreateModal')
            ->set('formName', '')
            ->set('formQuantity', -1)
            ->set('formMinStock', -1)
            ->set('formStatus', 'invalid_status')
            ->call('saveStock')
            ->assertHasErrors(['formName', 'formQuantity', 'formMinStock', 'formStatus']);

        // Success
        Livewire::test(StockList::class)
            ->call('openCreateModal')
            ->set('formName', 'Barang Baru')
            ->set('formSku', 'SKU-NEW-01')
            ->set('formDescription', 'Deskripsi barang baru')
            ->set('formQuantity', 10)
            ->set('formUnit', 'pcs')
            ->set('formPrice', '25000')
            ->set('formMinStock', 3)
            ->set('formStatus', 'available')
            ->call('saveStock')
            ->assertHasNoErrors()
            ->assertSet('showFormModal', false);

        $this->assertDatabaseHas('stocks', [
            'user_id' => $user->id,
            'name' => 'Barang Baru',
            'sku' => 'SKU-NEW-01',
            'quantity' => 10,
            'unit' => 'pcs',
            'status' => 'available',
        ]);
    }

    public function test_edit_stock_success(): void
    {
        $user = User::factory()->member()->create();
        $this->actingAs($user);

        $stock = Stock::factory()->create([
            'user_id' => $user->id,
            'name' => 'Nama Lama',
            'quantity' => 5,
        ]);

        Livewire::test(StockList::class)
            ->call('openEditModal', $stock->id)
            ->assertSet('editingStockId', $stock->id)
            ->assertSet('formName', 'Nama Lama')
            ->set('formName', 'Nama Telah Diedit')
            ->set('formQuantity', 12)
            ->call('saveStock')
            ->assertHasNoErrors()
            ->assertSet('showFormModal', false);

        $this->assertDatabaseHas('stocks', [
            'id' => $stock->id,
            'name' => 'Nama Telah Diedit',
            'quantity' => 12,
        ]);
    }

    public function test_adjust_quantity(): void
    {
        $user = User::factory()->member()->create();
        $this->actingAs($user);

        $stock = Stock::factory()->create([
            'user_id' => $user->id,
            'quantity' => 6,
            'min_stock' => 5,
            'status' => 'available',
        ]);

        // Decrement by 1 -> qty 5, status becomes low_stock
        Livewire::test(StockList::class)
            ->call('adjustQuantity', $stock->id, -1);

        $fresh = $stock->fresh();
        $this->assertEquals(5, $fresh->quantity);
        $this->assertEquals(StockStatus::LOW_STOCK->value, $fresh->status);

        // Increment by 2 -> qty 7, status becomes available
        Livewire::test(StockList::class)
            ->call('adjustQuantity', $stock->id, 2);

        $fresh = $stock->fresh();
        $this->assertEquals(7, $fresh->quantity);
        $this->assertEquals(StockStatus::AVAILABLE->value, $fresh->status);
    }

    public function test_delete_stock(): void
    {
        $user = User::factory()->member()->create();
        $this->actingAs($user);

        $stock = Stock::factory()->create(['user_id' => $user->id]);

        Livewire::test(StockList::class)
            ->call('deleteStock', $stock->id);

        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
    }

    public function test_confirm_and_cancel_delete_stock(): void
    {
        $user = User::factory()->member()->create();
        $this->actingAs($user);

        $stock = Stock::factory()->create(['user_id' => $user->id]);

        Livewire::test(StockList::class)
            ->assertSet('confirmingStockDeletionId', null)
            ->call('confirmDelete', $stock->id)
            ->assertSet('confirmingStockDeletionId', $stock->id)
            ->call('cancelDelete')
            ->assertSet('confirmingStockDeletionId', null)
            ->call('confirmDelete', $stock->id)
            ->call('deleteStock')
            ->assertSet('confirmingStockDeletionId', null);

        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
    }

    public function test_search_and_status_filter(): void
    {
        $user = User::factory()->member()->create();
        $this->actingAs($user);

        Stock::factory()->create([
            'user_id' => $user->id,
            'name' => 'Mouse Gaming',
            'status' => 'available',
        ]);
        Stock::factory()->create([
            'user_id' => $user->id,
            'name' => 'Keyboard Mechanical',
            'status' => 'out_of_stock',
        ]);

        Livewire::test(StockList::class)
            ->set('search', 'Gaming')
            ->assertSee('Mouse Gaming')
            ->assertDontSee('Keyboard Mechanical')
            ->set('search', '')
            ->set('statusFilter', 'out_of_stock')
            ->assertSee('Keyboard Mechanical')
            ->assertDontSee('Mouse Gaming');
    }

    public function test_export_excel(): void
    {
        $user = User::factory()->member()->create();
        $this->actingAs($user);

        Stock::factory()->create(['user_id' => $user->id, 'name' => 'Exportable Stock']);

        Livewire::test(StockList::class)
            ->call('exportExcel')
            ->assertFileDownloaded();
    }

    public function test_import_excel(): void
    {
        $user = User::factory()->member()->create();
        $this->actingAs($user);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'No');
        $sheet->setCellValue('B1', 'Name');
        $sheet->setCellValue('C1', 'SKU');
        $sheet->setCellValue('D1', 'Quantity');
        $sheet->setCellValue('E1', 'Unit');
        $sheet->setCellValue('F1', 'Price');
        $sheet->setCellValue('G1', 'Min Stock');
        $sheet->setCellValue('H1', 'Status');
        $sheet->setCellValue('I1', 'Description');

        $sheet->setCellValue('A2', '1');
        $sheet->setCellValue('B2', 'Imported Stock Item');
        $sheet->setCellValue('C2', 'SKU-IMP-01');
        $sheet->setCellValue('D2', '15');
        $sheet->setCellValue('E2', 'pcs');
        $sheet->setCellValue('F2', '50000');
        $sheet->setCellValue('G2', '5');
        $sheet->setCellValue('H2', 'available');
        $sheet->setCellValue('I2', 'Description text');

        $tempPath = tempnam(sys_get_temp_dir(), 'test_upload_').'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        $fileContent = file_get_contents($tempPath);
        @unlink($tempPath);

        $uploadedFile = UploadedFile::fake()->createWithContent('stocks.xlsx', $fileContent);

        Livewire::test(StockList::class)
            ->call('openImportModal')
            ->assertSet('showImportModal', true)
            ->set('importFile', $uploadedFile)
            ->call('importExcel')
            ->assertSet('showImportModal', false);

        $this->assertDatabaseHas('stocks', [
            'user_id' => $user->id,
            'name' => 'Imported Stock Item',
            'sku' => 'SKU-IMP-01',
            'quantity' => 15,
        ]);
    }

    public function test_ai_generation_and_saving(): void
    {
        $user = User::factory()->member()->create();
        $this->actingAs($user);

        // Mock AiClientInterface using createStub
        $mockAiClient = $this->createStub(AiClientInterface::class);
        $mockAiClient->method('generateStocks')->willReturn([
            new GeneratedStockItem('AI Product 1', 'SKU-AI-01', 'Desc 1', 10, 'pcs', 10000.0, 5, 'available'),
            new GeneratedStockItem('AI Product 2', 'SKU-AI-02', 'Desc 2', 20, 'box', 25000.0, 5, 'available'),
        ]);
        $this->app->instance(AiClientInterface::class, $mockAiClient);

        // Test validation for prompt and count
        Livewire::test(StockList::class)
            ->call('openAiModal')
            ->set('aiPrompt', '')
            ->set('aiCount', 0)
            ->call('generateWithAi')
            ->assertHasErrors(['aiPrompt', 'aiCount']);

        $component = Livewire::test(StockList::class)
            ->call('openAiModal')
            ->assertSet('showAiModal', true)
            ->set('aiPrompt', 'Buatkan barang toko')
            ->set('aiCount', 2)
            ->call('generateWithAi')
            ->assertCount('aiGeneratedItems', 2)
            ->assertCount('aiSelectedIndices', 2);

        // Try saving with no items selected
        $component->set('aiSelectedIndices', [])
            ->call('saveAiGenerated')
            ->assertSet('aiErrorMessage', 'Pilih minimal satu item stok yang ingin disimpan.');

        // Save selected items
        $component->set('aiSelectedIndices', [0, 1])
            ->call('saveAiGenerated')
            ->assertSet('showAiModal', false);

        $this->assertDatabaseHas('stocks', ['user_id' => $user->id, 'name' => 'AI Product 1']);
        $this->assertDatabaseHas('stocks', ['user_id' => $user->id, 'name' => 'AI Product 2']);
    }

    public function test_ai_generation_handles_exception(): void
    {
        $user = User::factory()->member()->create();
        $this->actingAs($user);

        $mockAiClient = $this->createStub(AiClientInterface::class);
        $mockAiClient->method('generateStocks')->willThrowException(new \RuntimeException('API quota exceeded'));
        $this->app->instance(AiClientInterface::class, $mockAiClient);

        Livewire::test(StockList::class)
            ->call('openAiModal')
            ->set('aiPrompt', 'Error test')
            ->call('generateWithAi')
            ->assertSet('aiErrorMessage', 'API quota exceeded');
    }

    public function test_close_modals(): void
    {
        $user = User::factory()->member()->create();
        $this->actingAs($user);

        Livewire::test(StockList::class)
            ->call('openCreateModal')
            ->call('closeFormModal')
            ->assertSet('showFormModal', false)
            ->call('openImportModal')
            ->call('closeImportModal')
            ->assertSet('showImportModal', false)
            ->call('openAiModal')
            ->call('closeAiModal')
            ->assertSet('showAiModal', false);
    }
}
