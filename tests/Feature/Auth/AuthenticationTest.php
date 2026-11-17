<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\ClassModel;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_wali_with_dashboard_as_intended_url_is_sent_to_their_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'wali_kelas']);
        ClassModel::create([
            'name' => 'XI RPL',
            'academic_year' => '2026/2027',
            'teacher_id' => $user->id,
        ]);

        $this->get('/dashboard')->assertRedirect('/login');
        $this->followingRedirects()
            ->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertOk()
            ->assertSee('Dashboard wali kelas');
        $this->assertAuthenticatedAs($user);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_expired_login_csrf_returns_to_login_with_a_fresh_form_notice(): void
    {
        $response = app(ExceptionHandler::class)->render(
            Request::create('/login', 'POST'),
            new TokenMismatchException
        );

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(route('login', ['session_expired' => 1]), $response->headers->get('Location'));
        $this->get(route('login', ['session_expired' => 1]))
            ->assertOk()
            ->assertSee('Sesi login telah berakhir. Silakan masuk kembali.');
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
