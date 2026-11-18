<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use App\Services\SchoolContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->isSiswa()) {
            return redirect()->route('student.dashboard');
        }
        if ($user->isWaliKelas()) {
            return redirect()->route('walikelas.dashboard');
        }
        abort_unless($user->isBendahara(), 403);

        // Grafik menampilkan seluruh riwayat kas sejak transaksi pertama sampai hari ini.
        $earliestTransaction = Transaction::min('transaction_date');
        $chartStart = $earliestTransaction
            ? Carbon::parse($earliestTransaction)->max(now()->subYear())
            : now()->subDays(6);
        $chartEnd = now();

        $reportRequest = Request::create('/', 'GET', [
            'start_date' => $chartStart->toDateString(),
            'end_date' => $chartEnd->toDateString(),
        ]);
        $data = app(ReportController::class)->data($reportRequest);
        $data['chartPeriodLabel'] = $chartStart->format('d M Y').' – '.$chartEnd->format('d M Y');
        $data['totalIncome'] = Transaction::ofType('income')->sum('amount');
        $data['totalExpense'] = Transaction::ofType('expense')->sum('amount');
        $data['rekapPerKategori'] = Transaction::select('category_id', DB::raw('COUNT(*) as total_data'), DB::raw('SUM(amount) as total_nominal'))
                ->groupBy('category_id')
                ->orderByDesc('total_nominal')
                ->get();
        $data['balance'] = $data['totalIncome'] - $data['totalExpense'];
        $data['studentCount'] = User::where('role', 'siswa')->whereHas('classMembers', fn ($q) => $q->where('class_id', SchoolContext::forUser(auth()->user())?->id)->where('is_active', true))->count();
        $data['recentTransactions'] = Transaction::orderByDesc('transaction_date')->orderByDesc('id')->limit(5)->get();

        return view('dashboard', $data);
    }
}
