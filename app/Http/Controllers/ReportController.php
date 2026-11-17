<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        return view('reports.index', $this->data($request));
    }

    public function exportPdf(Request $request)
    {
        return view('reports.print', $this->data($request));
    }

    public function data(Request $request): array
    {
        $request->merge([
            'start_date' => $request->input('start_date', now()->startOfMonth()->toDateString()),
            'end_date' => $request->input('end_date', now()->toDateString()),
        ]);
        $request->validate(['start_date' => ['required', 'date_format:Y-m-d']]);
        $dates = $request->validate([
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date', 'before_or_equal:'.Carbon::parse($request->input('start_date'))->addYear()->toDateString()],
        ]);
        $startDate = $dates['start_date'];
        $endDate = $dates['end_date'];
        $records = Transaction::dateRange($startDate, $endDate)->orderByDesc('transaction_date')->orderByDesc('id')->get();
        $totalIncome = $records->where('type', 'income')->sum('amount');
        $totalExpense = $records->where('type', 'expense')->sum('amount');
        $balance = $totalIncome - $totalExpense;
        $transactions = $records->map(fn ($row) => ['date' => $row->transaction_date->format('d M Y'), 'description' => $row->description, 'amount' => $row->amount, 'type' => $row->type]);
        $days = $records->groupBy(fn ($row) => $row->transaction_date->toDateString());
        $chartLabels = $chartIncome = $chartExpense = [];
        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            $rows = $days->get($date->toDateString(), collect());
            $chartLabels[] = $date->format('d M');
            $chartIncome[] = $rows->where('type', 'income')->sum('amount');
            $chartExpense[] = $rows->where('type', 'expense')->sum('amount');
        }

        return compact('totalIncome', 'totalExpense', 'balance', 'transactions', 'chartLabels', 'chartIncome', 'chartExpense', 'startDate', 'endDate');
    }
}
