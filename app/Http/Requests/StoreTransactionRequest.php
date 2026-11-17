<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // S2-05: Validasi tanggal tidak boleh di masa depan
            'date' => 'required|date|before_or_equal:today',
            
            // S2-05: Validasi jenis transaksi
            'type' => 'required|in:income,expense',
            
            // S2-05: Validasi kategori harus ada
            'category' => 'required|string|max:50',
            
            // S2-05: Validasi keterangan wajib dan max 255 char
            'description' => 'required|string|max:255',
            
            // S2-05: Validasi nominal harus angka positif, min 1000
            'amount' => 'required|numeric|min:1000|max:999999999',
            
            // S2-05: Validasi file upload bukti transaksi
            'proof' => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:2048', // Maksimal 2MB
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' => 'Tanggal transaksi wajib diisi.',
            'date.before_or_equal' => 'Tanggal tidak boleh di masa depan.',
            'type.required' => 'Jenis transaksi wajib dipilih.',
            'type.in' => 'Jenis transaksi tidak valid.',
            'category.required' => 'Kategori wajib dipilih.',
            'description.required' => 'Keterangan wajib diisi.',
            'description.max' => 'Keterangan maksimal 255 karakter.',
            'amount.required' => 'Nominal wajib diisi.',
            'amount.numeric' => 'Nominal harus berupa angka.',
            'amount.min' => 'Nominal minimal Rp 1.000.',
            'proof.image' => 'File harus berupa gambar.',
            'proof.mimes' => 'Format gambar hanya JPG, JPEG, PNG, atau WEBP.',
            'proof.max' => 'Ukuran file bukti maksimal 2MB.',
        ];
    }
}