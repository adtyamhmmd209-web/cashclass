@extends('layouts.bendahara')

@section('title', isset($transaction) ? 'Edit Transaksi' : 'Tambah Transaksi')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">{{ isset($transaction) ? 'Edit Transaksi' : 'Tambah Transaksi' }}</h1>
            <p class="text-gray-500 mt-1">Catat pemasukan atau pengeluaran kas kelas.</p>
        </div>
        <a href="{{ route('transactions.index') }}" class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Form -->
        <div class="lg:col-span-2 bg-white rounded-xl p-6 border border-gray-100 shadow-sm">
            <form method="POST" action="{{ isset($transaction) ? route('transactions.update', $transaction->id) : route('transactions.store') }}" enctype="multipart/form-data" class="space-y-5">
                @csrf
                @isset($transaction) @method('PUT') @endisset
                
                <!-- Tanggal -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Tanggal</label>
                    <input type="date" name="date" value="{{ old('date', isset($transaction) ? $transaction->transaction_date->format('Y-m-d') : now()->toDateString()) }}" required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('date') border-red-500 @enderror">
                    @error('date')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            
                <!-- Jenis Transaksi -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Jenis Transaksi</label>
                    <div class="flex gap-3">
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="type" value="income" class="peer sr-only" {{ old('type', $transaction->type ?? 'income') === 'income' ? 'checked' : '' }}>
                            <div class="peer-checked:bg-green-50 peer-checked:border-green-500 peer-checked:text-green-700 border-2 border-gray-200 rounded-lg py-2.5 text-center text-sm font-medium text-gray-600 transition">
                                Pemasukan
                            </div>
                        </label>
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="type" value="expense" class="peer sr-only" {{ old('type', $transaction->type ?? 'income') === 'expense' ? 'checked' : '' }}>
                            <div class="peer-checked:bg-red-50 peer-checked:border-red-500 peer-checked:text-red-700 border-2 border-gray-200 rounded-lg py-2.5 text-center text-sm font-medium text-gray-600 transition">
                                Pengeluaran
                            </div>
                        </label>
                    </div>
                    @error('type')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            
                <!-- Kategori -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Kategori</label>
                    <input name="category" list="transaction-categories" required maxlength="50" value="{{ old('category', $transaction->category?->name ?? '') }}" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg"><datalist id="transaction-categories"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('category') border-red-500 @enderror">
                        <option value="">Pilih kategori</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->name }}" {{ old('category', $transaction->category?->name ?? '') === $cat->name ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </datalist>
                    @error('category')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            
                <!-- Keterangan -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Keterangan</label>
                    <textarea name="description" rows="3" required placeholder="Contoh: Pembayaran kas - Andi Saputra"
                              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none @error('description') border-red-500 @enderror">{{ old('description', $transaction->description ?? '') }}</textarea>
                    @error('description')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            
                <!-- Nominal -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Nominal</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 text-sm font-medium">Rp</span>
                        <input type="text" name="amount" inputmode="numeric" maxlength="11" pattern="[0-9.]*" autocomplete="off"
                               value="{{ old('amount', isset($transaction) ? number_format((float) $transaction->amount, 0, '.', '') : '') }}"
                               required placeholder="0" aria-label="Nominal dalam rupiah"
                               class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg text-sm text-right tabular-nums focus:outline-none focus:ring-2 focus:ring-blue-500 @error('amount') border-red-500 @enderror">
                    </div>
                    @error('amount')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            
                <!-- Bukti Transaksi -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Bukti Transaksi <span class="text-gray-400 font-normal">(Opsional)</span></label>
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-500 transition cursor-pointer" onclick="document.getElementById('proof').click()">
                        <svg class="w-10 h-10 mx-auto text-gray-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        <p class="text-sm text-gray-600 font-medium">Pilih File</p>
                        <p id="proof-name" class="text-xs text-gray-400 mt-1">{{ isset($transaction) && $transaction->proof_image ? 'Bukti lama tetap digunakan jika tidak memilih file baru' : 'Tidak ada file yang dipilih' }}</p>
                        <p class="text-xs text-gray-400 mt-2">Format: JPG, PNG, WEBP (Maks. 2MB)</p>
                        <input onchange="document.getElementById('proof-name').textContent = this.files[0]?.name || 'Tidak ada file yang dipilih'" type="file" id="proof" name="proof" class="hidden" accept=".jpg,.jpeg,.png,.webp">
                    </div>
                    @error('proof')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            
                <!-- Buttons -->
                <div class="flex items-center justify-end gap-3 pt-4">
                    <a href="{{ route('transactions.index') }}" class="px-6 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                        Batal
                    </a>
                    <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition shadow-sm">
                        Simpan
                    </button>
                </div>
            </form>            
        </div>

        <!-- Contoh Kategori -->
        <div class="bg-white rounded-xl p-6 border border-gray-100 shadow-sm h-fit">
            <h3 class="text-sm font-bold text-gray-800 mb-4">Contoh Kategori</h3>
            
            <div class="space-y-4">
                <!-- Pemasukan -->
                <div>
                    <p class="text-xs font-semibold text-green-600 mb-2 flex items-center gap-1.5">
                        <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                        Pemasukan
                    </p>
                    <div class="space-y-2">
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <svg class="w-4 h-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                            </svg>
                            Pembayaran Kas
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <svg class="w-4 h-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                            </svg>
                            Sumbangan
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <svg class="w-4 h-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                            </svg>
                            Lain-lain
                        </div>
                    </div>
                </div>

                <!-- Pengeluaran -->
                <div>
                    <p class="text-xs font-semibold text-red-600 mb-2 flex items-center gap-1.5">
                        <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                        Pengeluaran
                    </p>
                    <div class="space-y-2">
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/>
                            </svg>
                            ATK
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/>
                            </svg>
                            Konsumsi
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/>
                            </svg>
                            Kegiatan
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/>
                            </svg>
                            Operasional
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/>
                            </svg>
                            Lain-lain
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const amountInput = document.querySelector('input[name="amount"]');
    if (!amountInput) return;

    const formatAmount = value => value.replace(/\D/g, '').slice(0, 9).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    const caretAfterDigits = (value, digitCount) => {
        if (digitCount === 0) return 0;
        let digitsSeen = 0;
        for (let index = 0; index < value.length; index++) {
            if (/\d/.test(value[index]) && ++digitsSeen === digitCount) return index + 1;
        }
        return value.length;
    };

    amountInput.value = formatAmount(amountInput.value);
    amountInput.addEventListener('input', function () {
        const digitCount = this.value.slice(0, this.selectionStart).replace(/\D/g, '').length;
        this.value = formatAmount(this.value);
        this.setSelectionRange(caretAfterDigits(this.value, digitCount), caretAfterDigits(this.value, digitCount));
    });
    amountInput.form.addEventListener('submit', function () {
        amountInput.value = amountInput.value.replace(/\D/g, '');
    });
});
</script>
@endpush
@endsection