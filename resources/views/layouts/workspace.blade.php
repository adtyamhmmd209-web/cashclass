@php
    $workspaceUser = auth()->user();
    $workspaceClass = \App\Services\SchoolContext::forUser($workspaceUser);
    $unreadNotificationsCount = $workspaceUser->unreadNotifications()->count();
    $roles = ['bendahara'=>'Bendahara kelas','siswa'=>'Siswa','wali_kelas'=>'Wali kelas'];
    $nav = match ($workspaceUser->role) {
        'bendahara' => [['dashboard','Dashboard','home','dashboard'],['transactions.index','Transaksi kas','wallet','transactions.*'],['class-fees.index','Tagihan kelas','payment','class-fees.*'],['payments.index','Pembayaran siswa','payment','payments.*'],['students.index','Data siswa','users','students.*'],['reports.index','Laporan keuangan','report','reports.*'],['settings.index','Pengaturan kelas','settings','settings.*']],
        'wali_kelas' => [['walikelas.dashboard','Dashboard','home','walikelas.dashboard'],['walikelas.class','Data kelas & akun','users','walikelas.class*'],['students.index','Data siswa','users','students.*'],['walikelas.announcements','Pengumuman','bell','walikelas.announcements'],['walikelas.reports','Laporan keuangan','report','walikelas.reports*']],
        default => [['student.dashboard','Dashboard','home','student.dashboard'],['student.fees','Tagihan kas','payment','student.fees'],['student.transactions','Transaksi & pembayaran','wallet','student.transactions'],['student.transactions.create','Transfer kas','payment','student.transactions.create'],['student.schedule','Jadwal kelas','calendar','student.schedule']],
    };
    $nav[] = ['notifications.index','Notifikasi','bell','notifications.*'];
    $nav[] = ['profile.edit','Profil & keamanan','user','profile.*'];
    $nav[] = $workspaceUser->isBendahara() ? ['error-reports.index','Laporan error','support','error-reports.*'] : ['error-reports.create','Laporkan masalah','support','error-reports.*'];
    $icons = ['home'=>'M3 10 12 3l9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1V10Z','wallet'=>'M20 7H5a2 2 0 0 1 0-4h13v4M3 5v14a2 2 0 0 0 2 2h15V7M20 12h-5v5h5','payment'=>'M3 5h18v14H3V5Zm0 5h18M6 15h4','users'=>'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm13 10v-2a4 4 0 0 0-3-4M16 3a4 4 0 0 1 0 8','user'=>'M20 21v-2a8 8 0 0 0-16 0v2M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z','report'=>'M6 3h9l4 4v14H5V3Zm3 6h6m-6 4h6m-6 4h4','calendar'=>'M3 5h18v16H3V5Zm0 5h18M7 2v6m10-6v6M7 14h2m3 0h2','bell'=>'M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4','settings'=>'M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm0-6v3m0 14v3M2 12h3m14 0h3M5 5l2 2m10 10 2 2M5 19l2-2M17 7l2-2','support'=>'M4 13V9a8 8 0 0 1 16 0v4M4 10H2v7h4v-7H4Zm16 0h2v7h-4v-7h2Zm0 7c0 4-3 4-6 4'];
@endphp
<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>CashClass — @yield('title','Profil & keamanan')</title>
@vite(['resources/css/app.css','resources/js/app.js'])<link rel="stylesheet" href="{{ asset('css/cashclass-workspace.css') }}?v=20261119-1">@stack('styles')</head>
<body class="cc-workspace"><button type="button" class="workspace-overlay" id="sidebar-overlay" aria-label="Tutup sidebar" hidden></button>
<aside class="workspace-sidebar" id="sidebar" aria-label="Sidebar CashClass">
<a class="workspace-brand" href="{{ route($workspaceUser->isSiswa()?'student.dashboard':($workspaceUser->isWaliKelas()?'walikelas.dashboard':'dashboard')) }}">@include('partials.cashclass-logo')</a>
<button type="button" class="workspace-close" data-sidebar-close aria-label="Tutup sidebar">&times;</button>
<div class="workspace-class"><small>Kelas Anda</small><strong>{{ $workspaceClass?->name ?? 'Belum ditetapkan' }}</strong><p>{{ $workspaceClass?->academic_year ?? 'Lengkapi pengaturan kelas' }}</p></div>
<nav aria-label="Navigasi utama">@foreach($nav as [$route,$label,$icon,$active])<a href="{{ route($route) }}" class="workspace-nav-link {{ request()->routeIs($active)?'active':'' }}" @if(request()->routeIs($active)) aria-current="page" @endif><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $icons[$icon] }}"/></svg>{{ $label }}@if($route==='notifications.index' && $unreadNotificationsCount>0)<span style="margin-left:auto;border-radius:999px;background:#ef4444;color:#fff;min-width:22px;height:22px;text-align:center;font-size:12px;line-height:22px">{{ $unreadNotificationsCount }}</span>@endif</a>@endforeach</nav>
<div class="workspace-user"><div class="workspace-avatar">{{ strtoupper(substr($workspaceUser->name,0,2)) }}</div><div><strong>{{ $workspaceUser->name }}</strong><small>{{ $roles[$workspaceUser->role] ?? 'Pengguna' }}</small></div><form method="POST" action="{{ route('logout') }}" class="workspace-logout">@csrf<button type="submit">Keluar dari CashClass</button></form></div>
</aside>
<div class="workspace-body"><header class="workspace-header"><button type="button" class="workspace-menu" data-sidebar-toggle aria-controls="sidebar" aria-expanded="false" aria-label="Buka sidebar"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button><div><h1>Kas Kelas Digital</h1><p>{{ $workspaceClass?->name ?? 'CashClass' }} &middot; {{ $roles[$workspaceUser->role] ?? '' }}</p></div><span class="workspace-date">{{ now()->translatedFormat('d F Y') }}</span></header>
<main class="workspace-content" id="main-content">@include('partials.feedback')
@if($workspaceClass && (!$workspaceClass->teacher_id || !$workspaceClass->treasurer_id))<div class="cc-notice cc-notice-error">Kelas belum memiliki bendahara yang ditugaskan.@if($workspaceUser->isWaliKelas()) <a href="{{ route('walikelas.class') }}">Tentukan bendahara</a>@else Hubungi wali kelas Anda.@endif</div>@endif
@isset($slot){{ $slot }}@else @yield('content') @endisset
</main></div>
<script>
document.addEventListener('DOMContentLoaded',function(){
 const sidebar=document.getElementById('sidebar'),overlay=document.getElementById('sidebar-overlay'),toggle=document.querySelector('[data-sidebar-toggle]');let previousFocus;
 function close(){document.body.classList.remove('cc-sidebar-open');overlay.hidden=true;toggle.setAttribute('aria-expanded','false');sidebar.inert=window.innerWidth<1024;if(previousFocus)previousFocus.focus();}
 function open(){previousFocus=document.activeElement;sidebar.inert=false;overlay.hidden=false;document.body.classList.add('cc-sidebar-open');toggle.setAttribute('aria-expanded','true');sidebar.querySelector('a').focus();}
 toggle.addEventListener('click',open);overlay.addEventListener('click',close);document.querySelector('[data-sidebar-close]').addEventListener('click',close);
 document.addEventListener('keydown',function(e){if(e.key==='Escape')close();if(e.key==='Tab'&&document.body.classList.contains('cc-sidebar-open')){const list=[...sidebar.querySelectorAll('a,button,input')].filter(x=>x.offsetParent!==null);const first=list[0],last=list[list.length-1];if(e.shiftKey&&document.activeElement===first){last.focus();e.preventDefault();}else if(!e.shiftKey&&document.activeElement===last){first.focus();e.preventDefault();}}});
 window.addEventListener('resize',function(){if(window.innerWidth>=1024)close();else sidebar.inert=!document.body.classList.contains('cc-sidebar-open');});sidebar.inert=window.innerWidth<1024;
  document.querySelectorAll('.workspace-content table').forEach(function(table){
    const labels=[...table.querySelectorAll('thead th')].map(function(th){return th.textContent.trim();});
    if(!labels.length)return;
    table.querySelectorAll('tbody tr').forEach(function(row){
      [...row.children].forEach(function(cell,index){
        if(labels[index]&&!cell.hasAttribute('data-label'))cell.setAttribute('data-label',labels[index]);
      });
    });
  });
});
</script>@stack('scripts')</body></html>
