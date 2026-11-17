<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200)
            ->assertSee('Daftar sebagai wali kelas')
            ->assertSee('Nama kelas')
            ->assertSee('Tahun ajaran')
            ->assertDontSee('name="role"');
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'class_name' => 'XI RPL',
            'academic_year' => '2026/2027',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('walikelas.dashboard', absolute: false));
        $this->assertDatabaseHas('users', ['email' => 'test@example.com', 'role' => 'wali_kelas']);
        $this->assertDatabaseHas('classes', [
            'name' => 'XI RPL',
            'academic_year' => '2026/2027',
            'teacher_id' => auth()->id(),
            'treasurer_id' => null,
        ]);
    }

    public function test_public_registration_cannot_create_a_student_or_treasurer_role(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'username' => 'test-user',
            'email' => 'test@example.com',
            'class_name' => 'XI RPL',
            'academic_year' => '2026/2027',
            'role' => 'bendahara',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('walikelas.dashboard', absolute: false));

        $this->assertDatabaseHas('users', ['email' => 'test@example.com', 'role' => 'wali_kelas']);
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com', 'role' => 'bendahara']);
    }
}
