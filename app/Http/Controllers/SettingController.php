<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\PaymentAccount;
use App\Services\SchoolContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    public function index()
    {
        $categories = ['income' => collect(), 'expense' => collect()];
        foreach (Category::orderBy('name')->get()->groupBy('type') as $type => $rows) {
            $categories[$type] = $rows;
        }
        $accounts = PaymentAccount::orderBy('type')->orderBy('provider')->get();

        return view('settings.index', compact('categories', 'accounts'));
    }

    public function storeAccount(Request $request)
    {
        $class = SchoolContext::requireForUser($request->user());
        $data = $request->validate([
            'type' => ['required', 'in:bank,e_wallet'], 'provider' => ['required', 'string', 'max:80'],
            'account_number' => ['required', 'string', 'regex:/^[0-9+ -]{5,50}$/'], 'account_holder' => ['required', 'string', 'max:100'],
        ]);
        $data['provider'] = strtoupper(trim($data['provider']));
        $data['account_number'] = preg_replace('/[ -]/', '', $data['account_number']);
        Validator::make($data, ['account_number' => ['required', 'regex:/^\+?[0-9]{5,49}$/']])->validate();
        PaymentAccount::updateOrCreate(['class_id' => $class->id, 'type' => $data['type'], 'provider' => $data['provider'], 'account_number' => $data['account_number']], ['account_holder' => $data['account_holder'], 'is_active' => true]);

        return back()->with('status', 'Tujuan pembayaran disimpan.');
    }

    public function toggleAccount(Request $request, PaymentAccount $account)
    {
        abort_unless($account->class_id === SchoolContext::requireForUser($request->user())->id, 404);
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        $account->update($data);

        return back()->with('status', 'Status tujuan pembayaran diperbarui.');
    }

    public function storeCategory(Request $request)
    {
        $class = SchoolContext::requireForUser($request->user());
        $data = $request->validate(['name' => ['required', 'string', 'max:50'], 'type' => ['required', 'in:income,expense']]);
        Category::firstOrCreate(['class_id' => $class->id, 'name' => $data['name'], 'type' => $data['type']], ['color' => $data['type'] === 'income' ? '#10b981' : '#ef4444']);

        return back()->with('status', 'Kategori berhasil ditambahkan.');
    }

    public function destroyCategory(Category $category)
    {
        if ($category->transactions()->exists()) {
            return back()->with('error', 'Kategori masih digunakan oleh transaksi.');
        }
        $category->delete();

        return back()->with('status', 'Kategori berhasil dihapus.');
    }
}
