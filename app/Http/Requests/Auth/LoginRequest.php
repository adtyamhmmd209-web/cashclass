<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['nullable', 'required_without:email', 'string', 'max:255'],
            'email' => ['nullable', 'required_without:username', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $loginIdentifier = ($this->input('username') ?: $this->input('email', ''));
        $loginType = filter_var($loginIdentifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (! Auth::attempt([
            $loginType => $loginIdentifier,
            'password' => $this->password,
        ], $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey($loginIdentifier));

            throw ValidationException::withMessages([
                'username' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey($loginIdentifier));
    }

    public function ensureIsNotRateLimited(): void
    {
        $loginIdentifier = ($this->input('username') ?: $this->input('email', ''));

        if (! RateLimiter::tooManyAttempts($this->throttleKey($loginIdentifier), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey($loginIdentifier));

        throw ValidationException::withMessages([
            'username' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(?string $loginIdentifier = null): string
    {
        $loginIdentifier ??= ($this->input('username') ?: $this->input('email', ''));

        return Str::transliterate(Str::lower($loginIdentifier).'|'.$this->ip());
    }
}
