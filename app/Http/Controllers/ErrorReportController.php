<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ErrorReportController extends Controller
{
    public function create(Request $request)
    {
        return view('error-reports.create', ['page' => Str::limit((string) parse_url($request->query('page', ''), PHP_URL_PATH), 500, '')]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'summary' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'page' => ['nullable', 'string', 'max:500'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);
        $id = (string) Str::uuid();
        $data['page'] = (string) parse_url($data['page'] ?? '', PHP_URL_PATH);
        $data += ['id' => $id, 'created_at' => now()->toIso8601String(), 'status' => 'open'];
        try {
            $saved = Storage::disk('local')->put('error-reports/'.$id.'.json', json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
            if (! $saved) {
                throw new \RuntimeException('Tidak dapat menyimpan laporan error.');
            }
        } catch (Throwable $e) {
            report($e);
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Laporan belum tersimpan. Silakan coba lagi.'], 503);
            }

            return back()->withInput()->with('error', 'Laporan belum tersimpan. Silakan coba lagi.');
        }
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Laporan tersimpan.', 'reference' => $id], 201);
        }

        return redirect()->route('error-reports.create')->with('reference', $id);
    }

    public function index()
    {
        $reports = collect(Storage::disk('local')->files('error-reports'))
            ->filter(fn ($file) => str_ends_with($file, '.json'))
            ->map(fn ($file) => json_decode(Storage::disk('local')->get($file), true))
            ->filter(fn ($data) => is_array($data) && isset($data['id'], $data['created_at'], $data['status']))
            ->sortByDesc('created_at')->values();

        return view('error-reports.index', compact('reports'));
    }

    public function update(Request $request, string $id)
    {
        $data = $request->validate(['status' => ['required', 'in:open,resolved']]);
        $file = 'error-reports/'.$id.'.json';
        abort_unless(Storage::disk('local')->exists($file), 404);
        $report = json_decode(Storage::disk('local')->get($file), true, 512, JSON_THROW_ON_ERROR);
        $report['status'] = $data['status'];
        $report['updated_at'] = now()->toIso8601String();
        if (! Storage::disk('local')->put($file, json_encode($report, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT))) {
            return back()->with('error', 'Status belum tersimpan. Silakan coba lagi.');
        }

        return back()->with('status', 'Status laporan diperbarui.');
    }
}
