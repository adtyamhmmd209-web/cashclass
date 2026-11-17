<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Laravel\Sanctum\PersonalAccessToken;

class AuthenticatedSessionController extends Controller
{
    /**
     * S2-01: Menampilkan halaman login.
     *
     * Route: GET /login
     * Akses: Public (guest)
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * S2-01: Memproses autentikasi pengguna.
     *
     * Route: POST /login
     * Akses: Public (guest)
     *
     * Logika:
     * 1. Validasi kredensial via LoginRequest
     * 2. Regenerate session untuk mencegah session fixation attack
     * 3. Redirect berdasarkan role pengguna:
     *    - siswa       → /student/dashboard
     *    - wali_kelas  → /walikelas/dashboard
     *    - bendahara   → /dashboard
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // 1. Autentikasi pengguna (validasi + login)
        $request->authenticate();

        // 2. Regenerate session ID untuk keamanan (mencegah session fixation)
        $request->session()->regenerate();

        // 3. Ambil data user yang baru saja login
        $user = Auth::user();

        // 4. Redirect berdasarkan role pengguna
        //    Ini adalah implementasi S2-01: Kontrol Akses Multi-Role
        if ($user->role === 'siswa') {
            return redirect()->intended(route('student.dashboard'))
                ->with('success', 'Selamat datang, '.$user->name.'!');
        }

        if ($user->role === 'wali_kelas') {
            return redirect()->intended(route('walikelas.dashboard'))
                ->with('success', 'Selamat datang, '.$user->name.'!');
        }

        // Default: bendahara diarahkan ke dashboard utama
        return redirect()->intended(route('dashboard'))
            ->with('success', 'Selamat datang, '.$user->name.'!');
    }

    /**
     * S2-01: Menghancurkan sesi autentikasi (logout).
     *
     * Route: POST /logout
     * Akses: Authenticated users
     *
     * Logika:
     * 1. Logout dari guard 'web'
     * 2. Invalidate session untuk menghapus semua data session
     * 3. Regenerate CSRF token untuk keamanan
     * 4. Redirect ke halaman login
     */
    public function destroy(Request $request): RedirectResponse
    {
        // 1. Logout dari guard web
        Auth::guard('web')->logout();

        // 2. Invalidate session (hapus semua data session)
        $request->session()->invalidate();

        // 3. Regenerate CSRF token untuk mencegah CSRF attack setelah logout
        $request->session()->regenerateToken();

        // 4. Redirect ke halaman login
        return redirect('/')
            ->with('success', 'Anda telah berhasil keluar dari sistem.');
    }

    /**
     * S2-03: Generate API Token untuk autentikasi API.
     *
     * Route: POST /api-token
     * Akses: Authenticated users (harus login dulu via web)
     *
     * Logika:
     * 1. Ambil user yang sedang login
     * 2. Hapus semua token lama (opsional, untuk keamanan)
     * 3. Buat token baru menggunakan Laravel Sanctum
     * 4. Return JSON dengan token dan info user
     *
     * Format respons JSON:
     * {
     *   "success": true,
     *   "message": "API token berhasil dibuat.",
     *   "data": {
     *     "token": "1|AbCdEf...",
     *     "token_type": "Bearer",
     *     "user": { ... }
     *   }
     * }
     */
    public function generateApiToken(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Validasi: pastikan user ada (seharusnya sudah dihandle middleware auth)
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak terautentikasi. Silakan login terlebih dahulu.',
            ], 401);
        }

        // Hapus semua token lama untuk keamanan (opsional)
        // Jika ingin multiple token, hapus baris ini
        $user->tokens()->delete();

        // Buat token baru dengan Laravel Sanctum
        // Parameter: nama token, abilities (permissions)
        $token = $user->createToken('cashclass-api-token', ['*'])->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'API token berhasil dibuat.',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'phone' => $user->phone,
                ],
                'expires_at' => null, // Token tidak kedaluwarsa (bisa diatur)
            ],
        ], 200);
    }

    /**
     * S2-03: Revoke (cabut) API Token.
     *
     * Route: DELETE /api-token
     * Akses: Authenticated users
     *
     * Berguna untuk logout dari aplikasi mobile/external
     */
    public function revokeApiToken(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Hapus token yang sedang digunakan (current token)
        $token = $user->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        } else {
            $user->tokens()->where('name', 'cashclass-api-token')->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'API token berhasil dicabut.',
        ], 200);
    }

    /**
     * S2-03: Revoke semua API Token user.
     *
     * Route: DELETE /api-token/all
     * Akses: Authenticated users
     */
    public function revokeAllApiTokens(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Hapus semua token user
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Semua API token berhasil dicabut.',
        ], 200);
    }
}
