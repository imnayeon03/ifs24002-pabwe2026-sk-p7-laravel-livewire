<?php

namespace Tests\Unit\Services;

use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Features\Stock\Repositories\StockRepository;
use App\Features\Stock\Services\StockService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    protected StockService $service;
    protected StockRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new StockRepository();
        $this->service = new StockService($this->repository);
    }

    public function test_get_stocks_for_user(): void
    {
        $user = User::factory()->create();
        Stock::factory()->count(2)->create(['user_id' => $user->id]);

        $paginator = $this->service->getStocksForUser($user->id);
        $this->assertCount(2, $paginator);
    }

    public function test_determine_status(): void
    {
        // Out of stock
        $this->assertEquals(StockStatus::OUT_OF_STOCK, $this->service->determineStatus(0, 5));
        $this->assertEquals(StockStatus::OUT_OF_STOCK, $this->service->determineStatus(-2, 5));

        // Low stock
        $this->assertEquals(StockStatus::LOW_STOCK, $this->service->determineStatus(3, 5));
        $this->assertEquals(StockStatus::LOW_STOCK, $this->service->determineStatus(5, 5));

        // Available
        $this->assertEquals(StockStatus::AVAILABLE, $this->service->determineStatus(6, 5));
        $this->assertEquals(StockStatus::AVAILABLE, $this->service->determineStatus(100, 5));
    }

    public function test_create_stock_with_default_and_custom_values(): void
    {
        $user = User::factory()->create();

        // With default quantity/min_stock
        $stock1 = $this->service->createStock($user->id, [
            'name' => 'Buku Tulis',
            'quantity' => 10,
            'min_stock' => 5,
        ]);

        $this->assertInstanceOf(Stock::class, $stock1);
        $this->assertEquals('Buku Tulis', $stock1->name);
        $this->assertEquals(10, $stock1->quantity);
        $this->assertEquals(StockStatus::AVAILABLE->value, $stock1->status);
        $this->assertEquals('pcs', $stock1->unit);

        // With explicit status and price
        $stock2 = $this->service->createStock($user->id, [
            'name' => 'Pensil 2B',
            'sku' => 'PNS-001',
            'description' => 'Pensil ujian',
            'quantity' => 2,
            'unit' => 'lusin',
            'price' => '30000',
            'min_stock' => 5,
            'status' => StockStatus::LOW_STOCK->value,
            'cover_image_path' => 'stock-covers/sample.jpg',
        ]);

        $this->assertEquals('PNS-001', $stock2->sku);
        $this->assertEquals('Pensil ujian', $stock2->description);
        $this->assertEquals('lusin', $stock2->unit);
        $this->assertEquals(30000.0, (float) $stock2->price);
        $this->assertEquals(StockStatus::LOW_STOCK->value, $stock2->status);
        $this->assertEquals('stock-covers/sample.jpg', $stock2->cover_image_path);
    }

    public function test_update_stock_recalculates_status(): void
    {
        $user = User::factory()->create();
        $stock = Stock::factory()->create([
            'user_id' => $user->id,
            'name' => 'Old Name',
            'quantity' => 15,
            'min_stock' => 5,
            'status' => StockStatus::AVAILABLE->value,
        ]);

        // Update quantity to 2 -> should recalculate status to low_stock
        $updated = $this->service->updateStock($stock, [
            'name' => 'Updated Name',
            'quantity' => 2,
            'price' => '',
        ]);

        $this->assertTrue($updated);
        $stock->refresh();
        $this->assertEquals('Updated Name', $stock->name);
        $this->assertEquals(2, $stock->quantity);
        $this->assertNull($stock->price);
        $this->assertEquals(StockStatus::LOW_STOCK->value, $stock->status);

        // Update with explicit status
        $this->service->updateStock($stock, [
            'status' => StockStatus::OUT_OF_STOCK->value,
        ]);
        $stock->refresh();
        $this->assertEquals(StockStatus::OUT_OF_STOCK->value, $stock->status);
    }

    public function test_adjust_quantity(): void
    {
        $user = User::factory()->create();
        $stock = Stock::factory()->create([
            'user_id' => $user->id,
            'quantity' => 10,
            'min_stock' => 5,
            'status' => StockStatus::AVAILABLE->value,
        ]);

        // Reduce by 6 (now 4, <= min_stock 5 -> low_stock)
        $this->service->adjustQuantity($stock, -6);
        $stock->refresh();
        $this->assertEquals(4, $stock->quantity);
        $this->assertEquals(StockStatus::LOW_STOCK->value, $stock->status);

        // Reduce by 10 (now clamped to 0 -> out_of_stock)
        $this->service->adjustQuantity($stock, -10);
        $stock->refresh();
        $this->assertEquals(0, $stock->quantity);
        $this->assertEquals(StockStatus::OUT_OF_STOCK->value, $stock->status);

        // Add 20 (now 20 > 5 -> available)
        $this->service->adjustQuantity($stock, 20);
        $stock->refresh();
        $this->assertEquals(20, $stock->quantity);
        $this->assertEquals(StockStatus::AVAILABLE->value, $stock->status);
    }

    public function test_cover_photo_management(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $stock = Stock::factory()->create(['user_id' => $user->id]);

        $fakeFile = UploadedFile::fake()->image('cover.jpg');

        // Update cover
        $path = $this->service->updateCoverPhoto($stock, $fakeFile);
        $stock->refresh();
        $this->assertEquals($path, $stock->cover_image_path);
        Storage::disk('public')->assertExists($path);

        // Update cover again (replaces old)
        $newFakeFile = UploadedFile::fake()->image('new-cover.jpg');
        $newPath = $this->service->updateCoverPhoto($stock, $newFakeFile);
        $stock->refresh();
        $this->assertEquals($newPath, $stock->cover_image_path);
        Storage::disk('public')->assertMissing($path);
        Storage::disk('public')->assertExists($newPath);

        // Delete cover
        $deleted = $this->service->deleteCoverPhoto($stock);
        $this->assertTrue($deleted);
        $stock->refresh();
        $this->assertNull($stock->cover_image_path);
        Storage::disk('public')->assertMissing($newPath);

        // Delete stock cleans up cover photo if present
        $anotherFile = UploadedFile::fake()->image('another.jpg');
        $anotherPath = $this->service->updateCoverPhoto($stock, $anotherFile);
        Storage::disk('public')->assertExists($anotherPath);

        $this->service->deleteStock($stock);
        Storage::disk('public')->assertMissing($anotherPath);
        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
    }
}
