<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()->latest()->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    public function read(Request $request, string $notification)
    {
        $item = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $item->markAsRead();

        // URL notifikasi lama bisa menunjuk ke host deployment lain (atau localhost).
        // Selalu arahkan kembali ke host yang sedang dipakai agar tombol "Lihat"
        // tidak membuka alamat yang salah.
        $target = $item->data['url'] ?? '/notifications';
        $parts = parse_url($target);
        if (is_array($parts) && isset($parts['path'])) {
            $target = $request->root().$parts['path'].(isset($parts['query']) ? '?'.$parts['query'] : '');
        }

        return redirect($target);
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('status', 'Semua notifikasi telah ditandai dibaca.');
    }
}
