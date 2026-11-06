<?php

namespace Database\Factories;

use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Stock>
 */
class StockFactory extends Factory
{
    protected $model = Stock::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $qty = fake()->numberBetween(0, 100);
        $min = 5;
        $status = $qty <= 0 ? 'out_of_stock' : ($qty <= $min ? 'low_stock' : 'available');

        return [
            'user_id' => User::factory(),
            'name' => fake()->words(2, true),
            'sku' => fake()->unique()->bothify('SKU-####-????'),
            'description' => fake()->paragraph(),
            'quantity' => $qty,
            'unit' => 'pcs',
            'price' => fake()->randomFloat(2, 1000, 500000),
            'min_stock' => $min,
            'status' => $status,
            'cover_image_path' => null,
        ];
    }

    /**
     * Indicate that the stock is available.
     */
    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 20,
            'min_stock' => 5,
            'status' => 'available',
        ]);
    }

    /**
     * Indicate that the stock is low.
     */
    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 3,
            'min_stock' => 5,
            'status' => 'low_stock',
        ]);
    }

    /**
     * Indicate that the stock is out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 0,
            'min_stock' => 5,
            'status' => 'out_of_stock',
        ]);
    }
}
