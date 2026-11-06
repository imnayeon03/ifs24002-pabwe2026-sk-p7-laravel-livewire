<?php

namespace Database\Seeders;

use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrator',
                'username' => 'admin',
                'password' => Hash::make('admin'),
                'role' => 'admin',
            ]
        );

        $member = User::firstOrCreate(
            ['email' => 'member@example.com'],
            [
                'name' => 'Member User',
                'username' => 'member',
                'password' => Hash::make('member'),
                'role' => 'member',
            ]
        );

        Stock::firstOrCreate(
            ['user_id' => $member->id, 'name' => 'Laptop ASUS VivoBook 14'],
            [
                'sku' => 'LAP-ASUS-01',
                'description' => 'Laptop kerja spesifikasi Core i5, RAM 16GB, SSD 512GB.',
                'quantity' => 12,
                'unit' => 'unit',
                'price' => 8500000,
                'min_stock' => 5,
                'status' => 'available',
            ]
        );

        Stock::firstOrCreate(
            ['user_id' => $member->id, 'name' => 'Mouse Wireless Logitech M330'],
            [
                'sku' => 'MOU-LOGI-01',
                'description' => 'Mouse silent click dengan konektivitas nirkabel 2.4GHz.',
                'quantity' => 3,
                'unit' => 'pcs',
                'price' => 175000,
                'min_stock' => 5,
                'status' => 'low_stock',
            ]
        );

        Stock::firstOrCreate(
            ['user_id' => $member->id, 'name' => 'Keyboard Mechanical TKL'],
            [
                'sku' => 'KBD-MECH-01',
                'description' => 'Keyboard mechanical switch red dengan lampu latar RGB.',
                'quantity' => 0,
                'unit' => 'pcs',
                'price' => 450000,
                'min_stock' => 3,
                'status' => 'out_of_stock',
            ]
        );
    }
}
