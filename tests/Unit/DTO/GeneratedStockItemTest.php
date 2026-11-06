<?php

namespace Tests\Unit\DTO;

use App\Features\Ai\DTO\GeneratedStockItem;
use PHPUnit\Framework\TestCase;

class GeneratedStockItemTest extends TestCase
{
    public function test_dto_properties_and_to_array(): void
    {
        $dto = new GeneratedStockItem(
            name: 'Laptop Gaming',
            sku: 'LAP-GAMING-01',
            description: 'Spesifikasi RTX 4060',
            quantity: 15,
            unit: 'unit',
            price: 18500000.0,
            minStock: 5,
            status: 'available'
        );

        $this->assertEquals('Laptop Gaming', $dto->name);
        $this->assertEquals('LAP-GAMING-01', $dto->sku);
        $this->assertEquals('Spesifikasi RTX 4060', $dto->description);
        $this->assertEquals(15, $dto->quantity);
        $this->assertEquals('unit', $dto->unit);
        $this->assertEquals(18500000.0, $dto->price);
        $this->assertEquals(5, $dto->minStock);
        $this->assertEquals('available', $dto->status);

        $array = $dto->toArray();
        $this->assertEquals([
            'name' => 'Laptop Gaming',
            'sku' => 'LAP-GAMING-01',
            'description' => 'Spesifikasi RTX 4060',
            'quantity' => 15,
            'unit' => 'unit',
            'price' => 18500000.0,
            'min_stock' => 5,
            'status' => 'available',
        ], $array);
    }
}
