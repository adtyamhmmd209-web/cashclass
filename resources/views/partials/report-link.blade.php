<div style="padding:16px;text-align:center;font:14px sans-serif;position:relative;z-index:10">
<a href="{{ route('error-reports.create', ['page' => request()->path()]) }}" style="color:#2563eb;text-decoration:underline">Kirim laporan error</a>
@auth @if(auth()->user()->isBendahara()) &middot; <a href="{{ route('error-reports.index') }}" style="color:#2563eb;text-decoration:underline">Laporan error masuk</a> @endif @endauth
</div>
