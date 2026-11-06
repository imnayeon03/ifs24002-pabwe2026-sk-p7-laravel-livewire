<div>
    <!-- Back & Header -->
    <div class="mb-6">
        <a href="{{ route('stocks.index') }}" class="inline-flex items-center space-x-1.5 text-sm font-medium text-slate-500 hover:text-blue-600 transition mb-3">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            <span>Kembali ke Daftar Stok</span>
        </a>

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="flex items-center space-x-3">
                    <h1 class="text-2xl font-bold text-slate-900">{{ $stock->name }}</h1>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $stock->statusEnum()->badgeClass() }}">
                        {{ $stock->statusEnum()->label() }}
                    </span>
                </div>
                <p class="text-sm font-mono text-slate-500 mt-1">SKU: {{ $stock->sku ?: 'Tidak ada SKU' }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <div class="inline-flex items-center space-x-2 bg-white border border-slate-200 rounded-xl px-3 py-1.5 shadow-sm">
                    <span class="text-xs font-semibold text-slate-500 uppercase mr-1">Sesuaikan:</span>
                    <button wire:click="adjustQuantity(-1)" class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center font-bold text-sm transition" title="Kurang 1">
                        -
                    </button>
                    <span class="font-bold text-slate-900 px-2 min-w-8 text-center">{{ $stock->quantity }}</span>
                    <button wire:click="adjustQuantity(1)" class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center font-bold text-sm transition" title="Tambah 1">
                        +
                    </button>
                </div>

                <button wire:click="openEditModal" class="inline-flex items-center space-x-2 px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-sm font-semibold shadow-sm transition">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    <span>Edit Informasi</span>
                </button>

                <button wire:click="confirmDeleteStock" class="inline-flex items-center space-x-2 px-4 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-xl text-sm font-semibold transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    <span>Hapus</span>
                </button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Column (2 cols): Cover & Description -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Cover Photo Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden p-6">
                <h3 class="text-sm font-bold uppercase text-slate-600 tracking-wider mb-4">Cover / Foto Barang</h3>

                @if ($stock->cover_image_path)
                    <div class="relative group rounded-xl overflow-hidden border border-slate-200 max-h-80 bg-slate-50 flex items-center justify-center">
                        <img src="{{ $stock->coverImageUrl() }}" alt="{{ $stock->name }}" class="w-full h-full object-contain max-h-80">
                        <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center space-x-3">
                            <button wire:click="confirmDeleteCover" class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-semibold shadow transition">
                                Hapus Foto
                            </button>
                        </div>
                    </div>
                @else
                    <div class="border-2 border-dashed border-slate-200 rounded-xl p-8 text-center bg-slate-50/50">
                        <svg class="w-12 h-12 text-slate-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <p class="text-sm text-slate-500 font-medium">Belum ada foto cover untuk barang ini</p>
                    </div>
                @endif

                <div class="mt-4">
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Unggah Foto Baru (Maks 5MB)</label>
                    <input type="file" wire:model="coverPhoto" accept="image/*" class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    <div wire:loading wire:target="coverPhoto" class="text-xs text-blue-600 mt-1 font-medium">Mengunggah foto...</div>
                    @error('coverPhoto') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Description Card (Markdown Supported) -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-bold uppercase text-slate-600 tracking-wider">Deskripsi & Spesifikasi</h3>
                    <span class="text-xs text-slate-400">Format Markdown</span>
                </div>

                @if ($stock->description)
                    <div class="prose prose-slate max-w-none text-sm text-slate-700 leading-relaxed">
                        {!! Illuminate\Support\Str::markdown($stock->description) !!}
                    </div>
                @else
                    <p class="text-sm text-slate-400 italic">Tidak ada deskripsi yang dituliskan untuk barang ini.</p>
                @endif
            </div>
        </div>

        <!-- Sidebar Column (1 col): Key Details -->
        <div class="space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-4">
                <h3 class="text-sm font-bold uppercase text-slate-600 tracking-wider border-b border-slate-100 pb-3">Detail Parameter Stok</h3>

                <div>
                    <span class="text-xs font-semibold uppercase text-slate-400">Jumlah Stok Sekarang</span>
                    <div class="text-2xl font-extrabold text-slate-900 mt-0.5">
                        {{ $stock->quantity }} <span class="text-sm font-semibold text-slate-500">{{ $stock->unit ?? 'pcs' }}</span>
                    </div>
                </div>

                <div>
                    <span class="text-xs font-semibold uppercase text-slate-400">Harga Satuan</span>
                    <div class="text-lg font-bold text-slate-800 mt-0.5">
                        {{ $stock->price !== null ? 'Rp ' . number_format($stock->price, 0, ',', '.') : 'Belum diatur' }}
                    </div>
                </div>

                <div>
                    <span class="text-xs font-semibold uppercase text-slate-400">Ambang Batas Minimum</span>
                    <div class="text-sm font-semibold text-slate-700 mt-0.5">
                        {{ $stock->min_stock }} {{ $stock->unit ?? 'pcs' }}
                    </div>
                    <p class="text-xs text-slate-400 mt-0.5">Status otomatis berubah jadi "Stok Menipis" jika &le; {{ $stock->min_stock }}</p>
                </div>

                <div>
                    <span class="text-xs font-semibold uppercase text-slate-400">Status Ketersediaan</span>
                    <div class="mt-1">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border {{ $stock->statusEnum()->badgeClass() }}">
                            {{ $stock->statusEnum()->label() }}
                        </span>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 space-y-2 text-xs text-slate-500">
                    <div class="flex justify-between">
                        <span>Ditambahkan pada:</span>
                        <span class="font-medium text-slate-700">{{ $stock->created_at ? $stock->created_at->format('d M Y H:i') : '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Terakhir diperbarui:</span>
                        <span class="font-medium text-slate-700">{{ $stock->updated_at ? $stock->updated_at->format('d M Y H:i') : '-' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Stock Modal -->
    @if ($showEditModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-slate-900/60" wire:click="closeEditModal"></div>

                <div class="inline-block w-full max-w-lg p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl sm:my-16">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                        <h3 class="text-lg font-bold text-slate-900">Edit Informasi Barang</h3>
                        <button wire:click="closeEditModal" class="text-slate-400 hover:text-slate-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form wire:submit="saveStock" class="mt-4 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Nama Barang *</label>
                            <input wire:model="name" type="text" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('name') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Kode SKU</label>
                                <input wire:model="sku" type="text" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono">
                                @error('sku') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Satuan</label>
                                <input wire:model="unit" type="text" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('unit') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Jumlah *</label>
                                <input wire:model="quantity" type="number" min="0" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('quantity') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Min Stok *</label>
                                <input wire:model="minStock" type="number" min="0" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('minStock') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Status</label>
                                <select wire:model="status" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="available">Tersedia</option>
                                    <option value="low_stock">Stok Menipis</option>
                                    <option value="out_of_stock">Habis</option>
                                </select>
                                @error('status') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Harga Satuan (Rp)</label>
                            <input wire:model="price" type="number" step="0.01" min="0" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('price') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Deskripsi (Markdown)</label>
                            <textarea wire:model="description" rows="3" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                            @error('description') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
                            <button type="button" wire:click="closeEditModal" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition">
                                Batal
                            </button>
                            <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-sm transition">
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Delete Confirm Modal -->
    @if ($confirmingStockDeletion)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-slate-900/60" wire:click="cancelDeleteStock"></div>
                <div class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl sm:my-16">
                    <h3 class="text-lg font-bold text-slate-900">Hapus Barang Stok</h3>
                    <p class="mt-2 text-sm text-slate-500">
                        Apakah Anda yakin ingin menghapus <strong>{{ $stock->name }}</strong>? Tindakan ini akan menghapus data dan foto cover secara permanen.
                    </p>
                    <div class="flex items-center justify-end space-x-3 mt-6">
                        <button wire:click="cancelDeleteStock" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition">
                            Batal
                        </button>
                        <button wire:click="deleteStock" class="px-4 py-2 text-sm font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-xl transition">
                            Ya, Hapus
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Delete Cover Confirm Modal -->
    @if ($confirmingCoverDeletion)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-slate-900/60" wire:click="cancelDeleteCover"></div>
                <div class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl sm:my-16">
                    <h3 class="text-lg font-bold text-slate-900">Hapus Foto Cover</h3>
                    <p class="mt-2 text-sm text-slate-500">
                        Apakah Anda yakin ingin menghapus foto cover dari barang ini?
                    </p>
                    <div class="flex items-center justify-end space-x-3 mt-6">
                        <button wire:click="cancelDeleteCover" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition">
                            Batal
                        </button>
                        <button wire:click="deleteCoverPhoto" class="px-4 py-2 text-sm font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-xl transition">
                            Hapus Cover
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
