<x-guest-layout>
    <h2>Selamat datang kembali!</h2>
    <p class="cc-intro">Masuk untuk melanjutkan pengelolaan kas kelas Anda.</p>
    @if(request()->boolean('session_expired'))<div class="cc-notice" role="status">Sesi login telah berakhir. Silakan masuk kembali.</div>@endif
    @if(session('status'))<div class="cc-notice" role="status">{{ session('status') }}</div>@endif
    <form method="POST" action="{{ route('login') }}" class="cc-form">
        @csrf
        <div class="cc-field"><label for="username">Username atau email</label><input id="username" name="username" type="text" value="{{ old('username', old('email')) }}" required autofocus autocomplete="username" placeholder="Masukkan username atau email" aria-invalid="{{ $errors->has('username') || $errors->has('email') ? 'true' : 'false' }}" aria-describedby="username-error"><div id="username-error" class="cc-error"><x-input-error :messages="array_merge($errors->get('username'), $errors->get('email'))" /></div></div>
        <div class="cc-field"><label for="password">Password</label><div class="cc-password"><input id="password" name="password" type="password" required autocomplete="current-password" placeholder="Masukkan password" data-password-label="password" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" aria-describedby="password-error"><button type="button" class="cc-password-toggle" data-toggle-password="password" aria-controls="password" aria-pressed="false" aria-label="Tampilkan password">Lihat</button></div><div id="password-error" class="cc-error"><x-input-error :messages="$errors->get('password')" /></div></div>
        <div class="cc-form-options"><label class="cc-remember"><input type="checkbox" name="remember" value="1" @checked(old('remember'))> Ingat saya</label><a href="{{ route('password.request') }}">Lupa password?</a></div>
        <button type="submit" class="cc-submit">Masuk ke CashClass <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 12h16m-6-6 6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
    </form>
    <p class="cc-bottom">Wali kelas belum punya akun? <a href="{{ route('register') }}">Daftar sebagai wali kelas</a></p>
</x-guest-layout>
