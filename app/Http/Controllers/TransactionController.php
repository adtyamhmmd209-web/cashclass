<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionRequest;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\FinanceNotification;
use App\Services\SchoolContext;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TransactionController extends Controller
{
    /**
     * S2-04: Tampilkan daftar transaksi (data dinamis dari DB)
     */
    public function index(Request $request)
    {
        $request->validate([
            'filter' => ['nullable', 'in:all,income,expense'],
            'search' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $filter = $request->get('filter', 'all');
        $search = $request->get('search', '');
        $categoryFilter = $request->get('category', '');

        // Query builder dengan filter
        $query = Transaction::with(['category', 'user'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc');

        // Filter by type
        if ($filter === 'income') {
            $query->ofType('income');
        } elseif ($filter === 'expense') {
            $query->ofType('expense');
        }

        // Filter by category
        if ($categoryFilter) {
            $query->whereHas('category', function ($q) use ($categoryFilter) {
                $q->where('name', $categoryFilter);
            });
        }

        // Search by description or category name
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhereHas('category', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Pagination 10 per halaman
        $transactions = $query->paginate(10)->withQueryString();

        // Statistik untuk summary
        $totalIncome = Transaction::ofType('income')->sum('amount');
        $totalExpense = Transaction::ofType('expense')->sum('amount');
        $balance = $totalIncome - $totalExpense;

        // Ambil semua kategori untuk filter dropdown
        $categories = Category::orderBy('name')->get();

        return view('transactions.index', compact(
            'transactions', 'filter', 'search', 'categoryFilter',
            'totalIncome', 'totalExpense', 'balance', 'categories'
        ));
    }

    /**
     * S2-04: Tampilkan form tambah transaksi
     */
    public function create()
    {
        $categories = Category::orderBy('name')->get();

        return view('transactions.create', compact('categories'));
    }

    public function store(StoreTransactionRequest $request, TransactionService $service)
    {
        try {
            $transaction = $service->save($request->validated(), (int) $request->user()->id, $request->file('proof'));

            if ($transaction->type === 'expense') {
                $this->notifyWaliKelas($request, 'Pengeluaran kas dicatat', 'Pengeluaran "'.$transaction->title.'" sebesar Rp '.number_format((float) $transaction->amount, 0, ',', '.').' telah dicatat di kas kelas.');
            }

            return redirect()->route('transactions.index')->with('success', 'Transaksi berhasil ditambahkan!');
        } catch (ValidationException|HttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'Gagal menyimpan transaksi. Silakan coba lagi atau kirim laporan error.');
        }
    }

    public function show($id)
    {
        $transaction = Transaction::with(['category', 'user'])->findOrFail($id);

        return view('transactions.show', compact('transaction'));
    }

    public function edit($id)
    {
        $transaction = Transaction::with('category')->findOrFail($id);
        $categories = Category::orderBy('name')->get();

        return view('transactions.edit', compact('transaction', 'categories'));
    }

    public function update(StoreTransactionRequest $request, $id, TransactionService $service)
    {
        $transaction = Transaction::findOrFail($id);
        try {
            $transaction = $service->save($request->validated(), (int) $request->user()->id, $request->file('proof'), $transaction);

            if ($transaction->type === 'expense') {
                $this->notifyWaliKelas($request, 'Pengeluaran diperbarui', 'Pengeluaran "'.$transaction->title.'" telah diperbarui menjadi Rp '.number_format((float) $transaction->amount, 0, ',', '.').'.');
            }

            return redirect()->route('transactions.index')->with('success', 'Transaksi berhasil diperbarui!');
        } catch (ValidationException|HttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'Gagal memperbarui transaksi. Silakan coba lagi atau kirim laporan error.');
        }
    }

    public function destroy(Request $request, $id, TransactionService $service)
    {
        $transaction = Transaction::findOrFail($id);
        $wasExpense = $transaction->type === 'expense';
        $expenseTitle = $transaction->title;
        $expenseAmount = $transaction->amount;
        try {
            $service->delete($transaction);

            if ($wasExpense) {
                $this->notifyWaliKelas($request, 'Pengeluaran dihapus', 'Pengeluaran "'.$expenseTitle.'" sebesar Rp '.number_format((float) $expenseAmount, 0, ',', '.').' telah dihapus dari kas kelas.');
            }

            return redirect()->route('transactions.index')->with('success', 'Transaksi berhasil dihapus!');
        } catch (ValidationException|HttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Gagal menghapus transaksi. Silakan coba lagi atau kirim laporan error.');
        }
    }

    private function notifyWaliKelas(Request $request, string $title, string $message): void
    {
        $class = SchoolContext::forUser($request->user());
        if (! $class || ! $class->teacher_id) {
            return;
        }

        User::where('role', 'wali_kelas')->whereKey($class->teacher_id)->first()
            ?->notify(new FinanceNotification([
                'title' => $title,
                'message' => $message,
                'url' => route('walikelas.reports'),
            ]));
    }

    public function proof($id)
    {
        $transaction = Transaction::findOrFail($id);
        abort_unless($transaction->proof_image && Storage::disk('public')->exists($transaction->proof_image), 404);

        return Storage::disk('public')->response($transaction->proof_image);
    }
}
