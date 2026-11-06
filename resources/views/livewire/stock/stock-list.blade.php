<div>
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Inventaris Stok Barang</h1>
            <p class="text-sm text-slate-500 mt-1">Kelola data stok barang, kuantitas, harga, dan peringatan batas minimum</p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <button wire:click="openCreateModal" class="inline-flex items-center space-x-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-semibold shadow-sm shadow-blue-500/20 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                <span>Tambah Barang</span>
            </button>

            <button wire:click="openAiModal" class="inline-flex items-center space-x-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-sm font-semibold shadow-sm shadow-purple-500/20 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                <span>AI Generator</span>
            </button>

            <button wire:click="openImportModal" class="inline-flex items-center space-x-2 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-sm font-semibold shadow-sm transition">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                <span>Impor</span>
            </button>

            <button wire:click="exportExcel" class="inline-flex items-center space-x-2 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-sm font-semibold shadow-sm transition">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Ekspor</span>
            </button>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200 mb-6 flex flex-col sm:flex-row gap-3">
        <div class="flex-1 relative">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama barang, SKU, deskripsi..." class="w-full pl-10 pr-4 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50/50">
            <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>

        <div class="w-full sm:w-48">
            <select wire:model.live="statusFilter" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50/50">
                <option value="">Semua Status</option>
                @foreach ($statuses as $st)
                    <option value="{{ $st->value }}">{{ $st->label() }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Stock Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <th class="py-3.5 px-6">Nama Barang</th>
                        <th class="py-3.5 px-6">SKU</th>
                        <th class="py-3.5 px-6 text-center">Jumlah Stok</th>
                        <th class="py-3.5 px-6">Satuan</th>
                        <th class="py-3.5 px-6">Harga</th>
                        <th class="py-3.5 px-6 text-center">Min. Stok</th>
                        <th class="py-3.5 px-6">Status</th>
                        <th class="py-3.5 px-6 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($stocks as $stock)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="py-4 px-6">
                                <div class="flex items-center space-x-3">
                                    @if ($stock->cover_image_path)
                                        <img src="{{ $stock->coverImageUrl() }}" alt="{{ $stock->name }}" class="w-10 h-10 rounded-lg object-cover border border-slate-200 flex-shrink-0">
                                    @else
                                        <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center text-slate-400 flex-shrink-0">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m4 10M4 7v10l8 4"/></svg>
                                        </div>
                                    @endif
                                    <div>
                                        <a href="{{ route('stocks.show', $stock) }}" class="font-semibold text-slate-900 hover:text-blue-600 transition">
                                            {{ $stock->name }}
                                        </a>
                                        @if ($stock->description)
                                            <p class="text-xs text-slate-400 truncate max-w-xs">{{ Str::limit($stock->description, 50) }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-6 font-mono text-xs text-slate-600">
                                {{ $stock->sku ?: '-' }}
                            </td>
                            <td class="py-4 px-6 text-center">
                                <div class="inline-flex items-center space-x-2">
                                    <button wire:click="adjustQuantity({{ $stock->id }}, -1)" class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center font-bold text-sm transition" title="Kurang 1">
                                        -
                                    </button>
                                    <span class="font-bold text-slate-900 min-w-8 text-center">{{ $stock->quantity }}</span>
                                    <button wire:click="adjustQuantity({{ $stock->id }}, 1)" class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center font-bold text-sm transition" title="Tambah 1">
                                        +
                                    </button>
                                </div>
                            </td>
                            <td class="py-4 px-6 text-slate-600">
                                {{ $stock->unit ?? 'pcs' }}
                            </td>
                            <td class="py-4 px-6 text-slate-700 font-medium">
                                {{ $stock->price !== null ? 'Rp ' . number_format($stock->price, 0, ',', '.') : '-' }}
                            </td>
                            <td class="py-4 px-6 text-center text-slate-500">
                                {{ $stock->min_stock }}
                            </td>
                            <td class="py-4 px-6">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $stock->statusEnum()->badgeClass() }}">
                                    {{ $stock->statusEnum()->label() }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-right space-x-2">
                                <a href="{{ route('stocks.show', $stock) }}" class="inline-flex p-1.5 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Lihat Detail">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                                <button wire:click="openEditModal({{ $stock->id }})" class="p-1.5 text-slate-500 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button wire:click="confirmDelete({{ $stock->id }})" class="p-1.5 text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Hapus">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-400">
                                Belum ada barang stok. Klik tombol <strong>Tambah Barang</strong> atau <strong>AI Generator</strong> untuk menambahkan data baru.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($stocks->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50">
                {{ $stocks->links() }}
            </div>
        @endif
    </div>

    <!-- Create / Edit Modal -->
    @if ($showFormModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-slate-900/60" wire:click="closeFormModal"></div>

                <div class="inline-block w-full max-w-lg p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl sm:my-16">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                        <h3 class="text-lg font-bold text-slate-900">
                            {{ $editingStockId ? 'Edit Barang Stok' : 'Tambah Barang Stok Baru' }}
                        </h3>
                        <button wire:click="closeFormModal" class="text-slate-400 hover:text-slate-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form wire:submit="saveStock" class="mt-4 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Nama Barang *</label>
                            <input wire:model="formName" type="text" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g. Laptop ASUS ZenBook">
                            @error('formName') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Kode SKU</label>
                                <input wire:model="formSku" type="text" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono" placeholder="e.g. SKU-1234">
                                @error('formSku') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Satuan</label>
                                <input wire:model="formUnit" type="text" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g. pcs, unit, box">
                                @error('formUnit') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Jumlah Stok *</label>
                                <input wire:model="formQuantity" type="number" min="0" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('formQuantity') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Ambang Min *</label>
                                <input wire:model="formMinStock" type="number" min="0" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('formMinStock') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Status</label>
                                <select wire:model="formStatus" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="available">Tersedia</option>
                                    <option value="low_stock">Stok Menipis</option>
                                    <option value="out_of_stock">Habis</option>
                                </select>
                                @error('formStatus') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Harga Satuan (Rp)</label>
                            <input wire:model="formPrice" type="number" step="0.01" min="0" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g. 150000">
                            @error('formPrice') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Deskripsi (Markdown didukung)</label>
                            <textarea wire:model="formDescription" rows="3" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Spesifikasi atau catatan barang..."></textarea>
                            @error('formDescription') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
                            <button type="button" wire:click="closeFormModal" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition">
                                Batal
                            </button>
                            <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-sm transition">
                                Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if ($confirmingStockDeletionId)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-slate-900/60" wire:click="cancelDelete"></div>
                <div class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl sm:my-16">
                    <h3 class="text-lg font-bold text-slate-900">Konfirmasi Hapus Barang</h3>
                    <p class="mt-2 text-sm text-slate-500">
                        Apakah Anda yakin ingin menghapus barang stok ini? Tindakan ini tidak dapat dibatalkan.
                    </p>
                    <div class="flex items-center justify-end space-x-3 mt-6">
                        <button wire:click="cancelDelete" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition">
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

    <!-- Excel Import Modal -->
    @if ($showImportModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-slate-900/60" wire:click="closeImportModal"></div>
                <div class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl sm:my-16">
                    <h3 class="text-lg font-bold text-slate-900">Impor Stok dari Excel</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Pilih file Excel (.xlsx, .xls, .csv) dengan format kolom: Nama, SKU, Jumlah, Satuan, Harga, Min Stok, Status, Deskripsi.
                    </p>

                    <form wire:submit="importExcel" class="mt-4 space-y-4">
                        <div>
                            <input wire:model="importFile" type="file" accept=".xlsx,.xls,.csv" class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            @error('importFile') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
                            <button type="button" wire:click="closeImportModal" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition">
                                Batal
                            </button>
                            <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl transition">
                                Impor Sekarang
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- AI Stock Generator Modal -->
    @if ($showAiModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-slate-900/60" wire:click="closeAiModal"></div>
                <div class="inline-block w-full max-w-2xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl sm:my-16">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                        <div class="flex items-center space-x-2">
                            <span class="p-1.5 bg-purple-100 text-purple-700 rounded-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </span>
                            <h3 class="text-lg font-bold text-slate-900">AI Stock Generator</h3>
                        </div>
                        <button wire:click="closeAiModal" class="text-slate-400 hover:text-slate-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="mt-4 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Topik atau Jenis Toko / Inventaris *</label>
                            <textarea wire:model="aiPrompt" rows="2" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="e.g. Buatkan 5 barang inventaris untuk toko peralatan komputer & gaming..."></textarea>
                            @error('aiPrompt') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex items-center space-x-4">
                            <div class="w-32">
                                <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Jumlah</label>
                                <input wire:model="aiCount" type="number" min="1" max="10" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-purple-500">
                            </div>

                            <div class="pt-5">
                                <button wire:click="generateWithAi" wire:loading.attr="disabled" class="inline-flex items-center space-x-2 px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-sm font-semibold transition disabled:opacity-50">
                                    <svg wire:loading.remove wire:target="generateWithAi" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    <svg wire:loading wire:target="generateWithAi" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                    <span>Generate Item Stok</span>
                                </button>
                            </div>
                        </div>

                        @if ($aiErrorMessage)
                            <div class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm">
                                {{ $aiErrorMessage }}
                            </div>
                        @endif

                        @if (!empty($aiGeneratedItems))
                            <div class="mt-4 border-t border-slate-100 pt-4">
                                <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Pratinjau Hasil AI (Pilih yang ingin disimpan):</h4>
                                <div class="space-y-2 max-h-60 overflow-y-auto">
                                    @foreach ($aiGeneratedItems as $idx => $item)
                                        <label class="flex items-start space-x-3 p-3 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer">
                                            <input type="checkbox" wire:model="aiSelectedIndices" value="{{ $idx }}" class="mt-1 rounded text-purple-600 focus:ring-purple-500">
                                            <div class="flex-1 text-sm">
                                                <div class="flex items-center justify-between">
                                                    <span class="font-semibold text-slate-900">{{ $item['name'] }}</span>
                                                    <span class="text-xs font-mono text-slate-500">{{ $item['sku'] ?? '-' }}</span>
                                                </div>
                                                <p class="text-xs text-slate-500 mt-0.5">{{ $item['description'] ?? 'Tanpa deskripsi' }}</p>
                                                <div class="mt-1 flex items-center space-x-4 text-xs text-slate-600">
                                                    <span>Qty: <strong>{{ $item['quantity'] }}</strong> {{ $item['unit'] }}</span>
                                                    <span>Harga: <strong>Rp {{ number_format($item['price'] ?? 0, 0, ',', '.') }}</strong></span>
                                                    <span>Min: <strong>{{ $item['min_stock'] }}</strong></span>
                                                </div>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
                            <button type="button" wire:click="closeAiModal" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition">
                                Tutup
                            </button>
                            @if (!empty($aiGeneratedItems))
                                <button type="button" wire:click="saveAiGenerated" class="px-5 py-2 text-sm font-semibold text-white bg-purple-600 hover:bg-purple-700 rounded-xl shadow-sm transition">
                                    Simpan Item Terpilih
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
