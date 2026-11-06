<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Migrations\Migration;

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

        // Jalankan seeder otomatis saat deployment di cloud jika user belum ada
        if (User::count() === 0) {
            $seeder = new DatabaseSeeder();
            $seeder->run();
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
