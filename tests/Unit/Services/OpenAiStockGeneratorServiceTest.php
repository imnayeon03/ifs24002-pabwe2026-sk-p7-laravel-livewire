<?php

namespace Tests\Unit\Services;

use App\Features\Ai\Services\OpenAiStockGeneratorService;
use App\Features\Stock\Enums\StockStatus;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class OpenAiStockGeneratorServiceTest extends TestCase
{
    public function test_empty_prompt_throws_exception(): void
    {
        $service = new OpenAiStockGeneratorService('https://api.openai.com/v1', 'mock-key', 'gpt-4o-mini');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Prompt tidak boleh kosong.');
        $service->generateStocks('   ');
    }

    public function test_successful_generation(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'stocks' => [
                                    [
                                        'name' => 'Kopi Arabika',
                                        'sku' => 'KOP-001',
                                        'description' => 'Biji kopi pilihan',
                                        'quantity' => 20,
                                        'unit' => 'kg',
                                        'price' => 120000,
                                        'min_stock' => 5,
                                    ],
                                    [
                                        'name' => 'Gula Pasir',
                                        'sku' => null,
                                        'description' => null,
                                        'quantity' => 0,
                                        'unit' => null,
                                        'price' => null,
                                        'min_stock' => null,
                                    ],
                                    [
                                        'name' => 'Susu Segar',
                                        'sku' => 'SUS-001',
                                        'description' => 'Susu murni',
                                        'quantity' => 3,
                                        'unit' => 'liter',
                                        'price' => 25000,
                                        'min_stock' => 5,
                                    ],
                                ],
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new OpenAiStockGeneratorService('https://api.openai.com/v1', 'mock-key', 'gpt-4o-mini');
        $results = $service->generateStocks('Buatkan 3 stok bahan kafe', 3);

        $this->assertCount(3, $results);

        $this->assertEquals('Kopi Arabika', $results[0]->name);
        $this->assertEquals('KOP-001', $results[0]->sku);
        $this->assertEquals('Biji kopi pilihan', $results[0]->description);
        $this->assertEquals(20, $results[0]->quantity);
        $this->assertEquals('kg', $results[0]->unit);
        $this->assertEquals(120000.0, $results[0]->price);
        $this->assertEquals(5, $results[0]->minStock);
        $this->assertEquals(StockStatus::AVAILABLE->value, $results[0]->status);

        $this->assertEquals('Gula Pasir', $results[1]->name);
        $this->assertNull($results[1]->sku);
        $this->assertNull($results[1]->description);
        $this->assertEquals(0, $results[1]->quantity);
        $this->assertEquals('pcs', $results[1]->unit); // default
        $this->assertNull($results[1]->price);
        $this->assertEquals(5, $results[1]->minStock); // default
        $this->assertEquals(StockStatus::OUT_OF_STOCK->value, $results[1]->status);

        $this->assertEquals('Susu Segar', $results[2]->name);
        $this->assertEquals(3, $results[2]->quantity);
        $this->assertEquals(StockStatus::LOW_STOCK->value, $results[2]->status);
    }

    public function test_api_failure_throws_exception(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'error' => [
                    'message' => 'Invalid API Key provided',
                ],
            ], 401),
        ]);

        $service = new OpenAiStockGeneratorService('https://api.openai.com/v1', 'mock-key', 'gpt-4o-mini');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OpenAI API Error: Invalid API Key provided');
        $service->generateStocks('Stok elektronik');
    }

    public function test_parse_content_with_markdown_fences(): void
    {
        $service = new OpenAiStockGeneratorService('https://api.openai.com/v1', 'mock-key', 'gpt-4o-mini');
        $raw = "```json\n[{\"name\": \"Mouse Wireless\", \"quantity\": 15}]\n```";

        $results = $service->parseContent($raw);
        $this->assertCount(1, $results);
        $this->assertEquals('Mouse Wireless', $results[0]->name);
        $this->assertEquals(15, $results[0]->quantity);
        $this->assertEquals(StockStatus::AVAILABLE->value, $results[0]->status);
    }

    public function test_parse_content_invalid_json(): void
    {
        $service = new OpenAiStockGeneratorService('https://api.openai.com/v1', 'mock-key', 'gpt-4o-mini');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('AI mengembalikan format respon yang tidak valid.');
        $service->parseContent('Not valid JSON');
    }

    public function test_parse_content_empty_stocks(): void
    {
        $service = new OpenAiStockGeneratorService('https://api.openai.com/v1', 'mock-key', 'gpt-4o-mini');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Tidak ada stok yang berhasil di-generate dari AI.');
        $service->parseContent('{"stocks": []}');
    }

    public function test_parse_content_no_valid_items(): void
    {
        $service = new OpenAiStockGeneratorService('https://api.openai.com/v1', 'mock-key', 'gpt-4o-mini');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Format item stok yang dikembalikan AI tidak sesuai spesifikasi.');
        $service->parseContent('{"stocks": [{"bad_key": "val"}]}');
    }

    public function test_constructor_handles_swapped_endpoint_and_key(): void
    {
        Http::fake([
            'https://ai.example.com/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => '{"stocks":[{"name":"Keyboard"}]}']]],
            ]),
        ]);

        // Key given as URL, endpoint given as secret
        $service = new OpenAiStockGeneratorService('sk-secret-key', 'https://ai.example.com/v1');
        $stocks = $service->generateStocks('Elektronik');
        $this->assertCount(1, $stocks);
    }

    public function test_constructor_auto_prefixes_https(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => '{"stocks":[{"name":"Monitor"}]}']]],
            ]),
        ]);

        $service = new OpenAiStockGeneratorService('api.openai.com/v1', 'mock-key');
        $stocks = $service->generateStocks('Elektronik');
        $this->assertCount(1, $stocks);
    }
}
