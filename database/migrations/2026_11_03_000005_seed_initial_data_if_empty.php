<?php

use App\Features\Stock\Models\Stock;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Jangan jalankan seeder otomatis saat testing PHPUnit
        if (app()->environment('testing')) {
            return;
        }

        // Selalu pastikan user admin dan member ada
        $admin = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name'     => 'Administrator',
                'email'    => 'admin@example.com',
                'password' => Hash::make('admin'),
                'role'     => 'admin',
            ]
        );

        $member = User::firstOrCreate(
            ['username' => 'member'],
            [
                'name'     => 'Member User',
                'email'    => 'member@example.com',
                'password' => Hash::make('member'),
                'role'     => 'member',
            ]
        );

        // Pastikan member memiliki minimal 3 stock
        if ($member->stocks()->count() === 0) {
            Stock::create([
                'user_id'     => $member->id,
                'name'        => 'Laptop ASUS VivoBook 14',
                'sku'         => 'LAP-ASUS-01',
                'description' => 'Laptop kerja spesifikasi Core i5, RAM 16GB, SSD 512GB.',
                'quantity'    => 12,
                'unit'        => 'unit',
                'price'       => 8500000,
                'min_stock'   => 5,
                'status'      => 'available',
            ]);

            Stock::create([
                'user_id'     => $member->id,
                'name'        => 'Mouse Wireless Logitech M330',
                'sku'         => 'MOU-LOGI-01',
                'description' => 'Mouse silent click dengan konektivitas nirkabel 2.4GHz.',
                'quantity'    => 3,
                'unit'        => 'pcs',
                'price'       => 175000,
                'min_stock'   => 5,
                'status'      => 'low_stock',
            ]);

            Stock::create([
                'user_id'     => $member->id,
                'name'        => 'Keyboard Mechanical TKL',
                'sku'         => 'KBD-MECH-01',
                'description' => 'Keyboard mechanical switch red dengan lampu latar RGB.',
                'quantity'    => 0,
                'unit'        => 'pcs',
                'price'       => 450000,
                'min_stock'   => 3,
                'status'      => 'out_of_stock',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
