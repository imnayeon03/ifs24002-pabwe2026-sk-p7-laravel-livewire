<div>
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-sm text-slate-500 mb-1">
                <a href="{{ route('admin.users') }}" class="hover:text-blue-600 transition flex items-center space-x-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    <span>Kembali ke Daftar Pengguna</span>
                </a>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 flex items-center space-x-2">
                <span>Stok Barang: {{ $user->name }}</span>
                <span class="px-2 py-0.5 text-xs font-semibold rounded bg-amber-100 text-amber-800 border border-amber-200">Read Only</span>
            </h1>
            <p class="text-sm text-slate-500 mt-1">Melihat inventaris stok milik pengguna secara langsung (Mode Hanya Baca)</p>
        </div>

        <div class="flex items-center space-x-3">
            <button wire:click="exportExcel" class="inline-flex items-center space-x-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-semibold shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span>Ekspor Excel</span>
            </button>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200 mb-6 flex flex-col sm:flex-row gap-3">
        <div class="flex-1 relative">
            <input wire:model.live.debounce.300ms="search" aria-label="Cari data" type="text" placeholder="Cari nama barang, SKU, atau deskripsi..." class="w-full pl-10 pr-4 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50/50">
            <svg class="w-4 h-4 text-slate-500 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>

        <div class="w-full sm:w-48">
            <select wire:model.live="statusFilter" aria-label="Filter status stok" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50/50">
                <option value="">Semua Status</option>
                @foreach ($statuses as $st)
                    <option value="{{ $st->value }}">{{ $st->label() }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <th class="py-3.5 px-6">Nama Barang</th>
                        <th class="py-3.5 px-6">SKU</th>
                        <th class="py-3.5 px-6 text-center">Jumlah</th>
                        <th class="py-3.5 px-6">Satuan</th>
                        <th class="py-3.5 px-6">Harga Satuan</th>
                        <th class="py-3.5 px-6 text-center">Min Stok</th>
                        <th class="py-3.5 px-6">Status</th>
                        <th class="py-3.5 px-6">Tanggal Dibuat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($stocks as $stock)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="py-4 px-6 font-semibold text-slate-900">
                                <div class="flex items-center space-x-3">
                                    @if ($stock->cover_image_path)
                                        <img src="{{ $stock->coverImageUrl() }}" alt="{{ $stock->name }}" class="w-9 h-9 rounded-lg object-cover border border-slate-200">
                                    @else
                                        <div class="w-9 h-9 rounded-lg bg-slate-100 flex items-center justify-center text-slate-500">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m4 10M4 7v10l8 4"/></svg>
                                        </div>
                                    @endif
                                    <div>
                                        <div class="font-medium text-slate-900">{{ $stock->name }}</div>
                                        @if ($stock->description)
                                            <div class="text-xs text-slate-500 truncate max-w-xs">{{ Str::limit($stock->description, 50) }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-6 font-mono text-xs text-slate-600">
                                {{ $stock->sku ?: '-' }}
                            </td>
                            <td class="py-4 px-6 text-center font-bold text-slate-900">
                                {{ $stock->quantity }}
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
                            <td class="py-4 px-6 text-slate-500 text-xs">
                                {{ $stock->created_at ? $stock->created_at->format('d M Y H:i') : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-500">
                                Tidak ada data stok barang yang ditemukan untuk pengguna ini.
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
</div>
