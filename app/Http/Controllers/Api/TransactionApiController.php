<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Models\Transaction;
use App\Services\TransactionService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TransactionApiController extends Controller
{
    /**
     * S2-03: GET /api/v1/transactions
     * Menampilkan daftar transaksi dengan filter dan pagination
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'filter' => ['nullable', 'in:all,income,expense'],
            'search' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $filter = $request->get('filter', 'all');
        $search = $request->get('search', '');
        $request->validate(['per_page' => ['nullable', 'integer', 'min:1', 'max:100']]);
        $perPage = $request->integer('per_page', 10);

        $query = Transaction::with(['category', 'user'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc');

        // Filter by type
        if ($filter === 'income') {
            $query->ofType('income');
        } elseif ($filter === 'expense') {
            $query->ofType('expense');
        }

        // Search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhereHas('category', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $transactions = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar transaksi berhasil diambil.',
            'data' => [
                'transactions' => $transactions->items(),
                'pagination' => [
                    'current_page' => $transactions->currentPage(),
                    'per_page' => $transactions->perPage(),
                    'total' => $transactions->total(),
                    'last_page' => $transactions->lastPage(),
                ],
            ],
        ], 200);
    }

    public function store(StoreTransactionRequest $request, TransactionService $service): JsonResponse
    {
        try {
            $validated = $request->validated();
            $dup = Transaction::where('user_id', (int) $request->user()->id)
                ->where('amount', $validated['amount'])
                ->where('description', $validated['description'] ?? null)
                ->exists();
            if ($dup) {
                throw ValidationException::withMessages([
                    'description' => 'Data duplikat: transaksi yang sama sudah pernah diinput.',
                ]);
            }

            $transaction = $service->save($validated, (int) $request->user()->id, $request->file('proof'));

            return response()->json(['success' => true, 'message' => 'Transaksi berhasil ditambahkan.', 'data' => $transaction->load(['category', 'user'])], 201);
        } catch (ValidationException|HttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['success' => false, 'message' => 'Gagal menyimpan transaksi.'], 500);
        }
    }

    public function show($id): JsonResponse
    {
        $transaction = Transaction::with(['category', 'user'])->findOrFail($id);

        return response()->json(['success' => true, 'data' => $transaction]);
    }

    public function update(StoreTransactionRequest $request, $id, TransactionService $service): JsonResponse
    {
        $transaction = Transaction::findOrFail($id);
        try {
            $transaction = $service->save($request->validated(), (int) $request->user()->id, $request->file('proof'), $transaction);

            return response()->json(['success' => true, 'message' => 'Transaksi berhasil diperbarui.', 'data' => $transaction->fresh(['category', 'user'])]);
        } catch (ValidationException|HttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['success' => false, 'message' => 'Gagal memperbarui transaksi.'], 500);
        }
    }

    public function destroy($id, TransactionService $service): JsonResponse
    {
        $transaction = Transaction::findOrFail($id);
        try {
            $service->delete($transaction);

            return response()->json(['success' => true, 'message' => 'Transaksi berhasil dihapus.']);
        } catch (ValidationException|HttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['success' => false, 'message' => 'Gagal menghapus transaksi.'], 500);
        }
    }

    public function dashboardStats(): JsonResponse
    {
        $totalIncome = Transaction::ofType('income')->sum('amount');
        $totalExpense = Transaction::ofType('expense')->sum('amount');
        $balance = $totalIncome - $totalExpense;
        $totalTransactions = Transaction::count();

        // Grafik mencakup seluruh riwayat kas sejak transaksi pertama sampai hari ini.
        $earliestTransaction = Transaction::min('transaction_date');
        $chartStart = $earliestTransaction
            ? Carbon::parse($earliestTransaction)->max(now()->subYear())
            : now()->subDays(6);
        $chartEnd = now();

        $records = Transaction::whereBetween('transaction_date', [$chartStart->toDateString(), $chartEnd->toDateString()])
            ->get(['transaction_date', 'type', 'amount']);
        $days = $records->groupBy(fn ($row) => $row->transaction_date->toDateString());

        $chartData = [];
        foreach (CarbonPeriod::create($chartStart->toDateString(), $chartEnd->toDateString()) as $date) {
            $rows = $days->get($date->toDateString(), collect());
            $chartData[] = [
                'date' => $date->toDateString(),
                'label' => $date->format('d M'),
                'income' => (float) $rows->where('type', 'income')->sum('amount'),
                'expense' => (float) $rows->where('type', 'expense')->sum('amount'),
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Statistik dashboard berhasil diambil.',
            'data' => [
                'summary' => [
                    'total_income' => $totalIncome,
                    'total_expense' => $totalExpense,
                    'balance' => $balance,
                    'total_transactions' => $totalTransactions,
                ],
                'chart' => $chartData,
                'recent_transactions' => Transaction::with(['category'])
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get(),
            ],
        ], 200);
    }
}
