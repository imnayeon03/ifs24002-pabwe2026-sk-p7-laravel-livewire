<?php

namespace Tests\Feature\Stock;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Livewire\StockDetail;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class StockDetailLivewireTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_stock_detail(): void
    {
        $stock = Stock::factory()->create();

        $this->get(route('stocks.show', $stock->id))
            ->assertRedirect(route('login'));
    }

    public function test_member_cannot_access_other_member_stock(): void
    {
        $owner = User::factory()->member()->create();
        $otherMember = User::factory()->member()->create();

        $stock = Stock::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($otherMember);

        $this->get(route('stocks.show', $stock->id))
            ->assertStatus(403);
    }

    public function test_member_can_view_own_stock_detail(): void
    {
        $user = User::factory()->member()->create();
        $stock = Stock::factory()->create([
            'user_id' => $user->id,
            'name' => 'Barang Elektronik Utama',
            'sku' => 'SKU-ELEC-01',
            'description' => '# Deskripsi Markdown',
            'quantity' => 20,
            'unit' => 'unit',
            'price' => 1500000.0,
            'min_stock' => 5,
            'status' => 'available',
        ]);

        $this->actingAs($user);

        Livewire::test(StockDetail::class, ['stock' => $stock])
            ->assertOk()
            ->assertSet('name', 'Barang Elektronik Utama')
            ->assertSet('sku', 'SKU-ELEC-01')
            ->assertSet('description', '# Deskripsi Markdown')
            ->assertSet('quantity', 20)
            ->assertSet('unit', 'unit')
            ->assertSet('status', 'available')
            ->assertSee('Barang Elektronik Utama');
    }

    public function test_open_and_close_edit_modal(): void
    {
        $user = User::factory()->member()->create();
        $stock = Stock::factory()->create([
            'user_id' => $user->id,
            'name' => 'Nama Detail',
            'description' => 'Deskripsi lama',
            'status' => 'available',
        ]);

        $this->actingAs($user);

        Livewire::test(StockDetail::class, ['stock' => $stock])
            ->assertSet('showEditModal', false)
            ->call('openEditModal')
            ->assertSet('showEditModal', true)
            ->assertSet('name', 'Nama Detail')
            ->assertSet('description', 'Deskripsi lama')
            ->call('closeEditModal')
            ->assertSet('showEditModal', false);
    }

    public function test_update_stock_validation_and_success(): void
    {
        $user = User::factory()->member()->create();
        $stock = Stock::factory()->create([
            'user_id' => $user->id,
            'name' => 'Nama Awal',
            'quantity' => 10,
            'min_stock' => 5,
        ]);

        $this->actingAs($user);

        // Validation errors
        Livewire::test(StockDetail::class, ['stock' => $stock])
            ->call('openEditModal')
            ->set('name', 'a') // min:2
            ->set('quantity', -1)
            ->set('status', 'invalid_status')
            ->call('saveStock')
            ->assertHasErrors(['name', 'quantity', 'status']);

        // Success
        Livewire::test(StockDetail::class, ['stock' => $stock])
            ->call('openEditModal')
            ->set('name', 'Nama Telah Diperbarui')
            ->set('sku', 'SKU-UPD-01')
            ->set('description', '## Rincian Baru')
            ->set('quantity', 25)
            ->set('unit', 'kotak')
            ->set('price', '75000')
            ->set('minStock', 10)
            ->set('status', 'available')
            ->call('saveStock')
            ->assertHasNoErrors()
            ->assertSet('showEditModal', false);

        $this->assertDatabaseHas('stocks', [
            'id' => $stock->id,
            'name' => 'Nama Telah Diperbarui',
            'sku' => 'SKU-UPD-01',
            'quantity' => 25,
            'unit' => 'kotak',
            'min_stock' => 10,
        ]);
    }

    public function test_adjust_quantity(): void
    {
        $user = User::factory()->member()->create();
        $stock = Stock::factory()->create([
            'user_id' => $user->id,
            'quantity' => 5,
            'min_stock' => 5,
            'status' => 'low_stock',
        ]);

        $this->actingAs($user);

        // Increment by 1 -> qty 6, status available
        Livewire::test(StockDetail::class, ['stock' => $stock])
            ->call('adjustQuantity', 1)
            ->assertSet('quantity', 6)
            ->assertSet('status', StockStatus::AVAILABLE->value);

        $this->assertDatabaseHas('stocks', [
            'id' => $stock->id,
            'quantity' => 6,
            'status' => 'available',
        ]);

        // Decrement by 6 -> qty 0, status out_of_stock
        Livewire::test(StockDetail::class, ['stock' => $stock->fresh()])
            ->call('adjustQuantity', -6)
            ->assertSet('quantity', 0)
            ->assertSet('status', StockStatus::OUT_OF_STOCK->value);

        $this->assertDatabaseHas('stocks', [
            'id' => $stock->id,
            'quantity' => 0,
            'status' => 'out_of_stock',
        ]);
    }

    public function test_cover_photo_upload_and_delete(): void
    {
        Storage::fake('public');

        $user = User::factory()->member()->create();
        $stock = Stock::factory()->create(['user_id' => $user->id, 'cover_image_path' => null]);

        $this->actingAs($user);

        // Invalid file
        $invalidFile = UploadedFile::fake()->create('document.pdf', 100);
        Livewire::test(StockDetail::class, ['stock' => $stock])
            ->set('coverPhoto', $invalidFile)
            ->assertHasErrors(['coverPhoto']);

        // Valid image upload
        $imageFile = UploadedFile::fake()->image('cover.jpg', 800, 400);
        $component = Livewire::test(StockDetail::class, ['stock' => $stock])
            ->set('coverPhoto', $imageFile)
            ->assertHasNoErrors();

        $stock->refresh();
        $this->assertNotNull($stock->cover_image_path);
        Storage::disk('public')->assertExists($stock->cover_image_path);

        // Delete cover photo
        $component->call('deleteCoverPhoto');

        $stock->refresh();
        $this->assertNull($stock->cover_image_path);
    }

    public function test_delete_stock_redirects(): void
    {
        $user = User::factory()->member()->create();
        $stock = Stock::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        Livewire::test(StockDetail::class, ['stock' => $stock])
            ->call('deleteStock')
            ->assertRedirect(route('stocks.index'));

        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
    }

    public function test_confirm_and_cancel_delete_modal_actions(): void
    {
        $user = User::factory()->member()->create();
        $stock = Stock::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        Livewire::test(StockDetail::class, ['stock' => $stock])
            ->assertSet('confirmingStockDeletion', false)
            ->call('confirmDeleteStock')
            ->assertSet('confirmingStockDeletion', true)
            ->call('cancelDeleteStock')
            ->assertSet('confirmingStockDeletion', false)
            ->assertSet('confirmingCoverDeletion', false)
            ->call('confirmDeleteCover')
            ->assertSet('confirmingCoverDeletion', true)
            ->call('cancelDeleteCover')
            ->assertSet('confirmingCoverDeletion', false);
    }
}
