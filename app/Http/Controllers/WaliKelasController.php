<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\ClassFeeAssignment;
use App\Models\Transaction;
use App\Models\User;
use App\Services\SchoolContext;
use Illuminate\Http\Request;

class WaliKelasController extends Controller
{
    private function context(Request $request): array
    {
        $class = SchoolContext::forUser($request->user());
        $students = $class ? User::where('role', 'siswa')->whereHas('classMembers', fn ($q) => $q->where('class_id', $class->id)->where('is_active', true))->orderBy('name')->get() : collect();

        return compact('class', 'students');
    }

    public function dashboard(Request $request)
    {
        $data = $this->context($request);
        $classId = $data['class']?->id;
        $data['income'] = Transaction::where('class_id', $classId)->ofType('income')->sum('amount');
        $data['expense'] = Transaction::where('class_id', $classId)->ofType('expense')->sum('amount');
        $data['announcements'] = Announcement::where('class_id', $classId)->orderByDesc('is_pinned')->latest()->limit(10)->get();
        $data['feeAssignments'] = ClassFeeAssignment::with(['user', 'classFee', 'payments'])
            ->where('class_id', $classId)
            ->whereHas('classFee', fn ($query) => $query->where('starts_at', '<=', today()))
            ->get();
        $data['feeTarget'] = $data['feeAssignments']->sum(fn ($assignment) => (float) $assignment->classFee->amount);
        $data['feeCollected'] = $data['feeAssignments']
            ->filter(fn ($assignment) => $assignment->payments->contains('status', 'approved'))
            ->sum(fn ($assignment) => (float) $assignment->classFee->amount);
        $data['feeOutstanding'] = max(0, $data['feeTarget'] - $data['feeCollected']);
        $data['feeOverdue'] = $data['feeAssignments']
            ->filter(fn ($assignment) => $assignment->classFee->due_date->lt(today()) && ! $assignment->payments->contains('status', 'approved'));
        $data['feeOverdueTotal'] = $data['feeOverdue']->sum(fn ($assignment) => (float) $assignment->classFee->amount);
        $data['studentsWithFeesDue'] = $data['feeAssignments']
            ->filter(fn ($assignment) => ! $assignment->payments->contains('status', 'approved'))
            ->groupBy('user_id')
            ->map(fn ($assignments) => [
                'name' => $assignments->first()->user->name,
                'amount' => $assignments->sum(fn ($assignment) => (float) $assignment->classFee->amount),
                'count' => $assignments->count(),
            ])
            ->sortByDesc('amount')
            ->take(5);

        return view('walikelas.dashboard', $data);
    }

    public function announcements(Request $request)
    {
        $data = $this->context($request);
        $data += [
            'type' => 'announcement',
            'records' => Announcement::where('class_id', $data['class']?->id)->latest('id')->paginate(20)->withQueryString(),
        ];

        return view('walikelas.records', $data);
    }

    public function storeAnnouncement(Request $request)
    {
        $class = SchoolContext::forUser($request->user());
        if (! $class) {
            return back()->withInput()->with('error', 'Data kelas belum tersedia pada akun wali kelas ini.');
        }
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:10000'],
            'is_pinned' => ['sometimes', 'boolean'],
        ]);
        Announcement::create($data + ['class_id' => $class->id, 'user_id' => $request->user()->id]);

        return back()->with('status', 'Data berhasil disimpan.');
    }

    public function destroyAnnouncement(Request $request, int $id)
    {
        $class = SchoolContext::forUser($request->user());
        abort_unless($class, 403);
        Announcement::where('class_id', $class->id)->findOrFail($id)->delete();

        return back()->with('status', 'Data berhasil dihapus.');
    }

    public function reports(Request $request)
    {
        return app(ReportController::class)->index($request);
    }

    public function exportPdf(Request $request)
    {
        return app(ReportController::class)->exportPdf($request);
    }

}
