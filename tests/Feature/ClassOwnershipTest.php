<?php

namespace Tests\Feature;

use App\Models\ClassMember;
use App\Models\User;
use App\Services\SchoolContext;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesCashClass;
use Tests\TestCase;

class ClassOwnershipTest extends TestCase
{
    use CreatesCashClass, RefreshDatabase;

    public function test_wali_can_create_and_replace_the_class_treasurer(): void
    {
        $group = $this->createCashClass();
        $this->actingAs($group['teacher'])->post('/walikelas/accounts', [
            'account_type' => 'bendahara',
            'name' => 'Bendahara Baru',
            'username' => 'bendahara-baru',
            'email' => 'bendahara-baru@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/walikelas/class');

        $newTreasurer = User::where('username', 'bendahara-baru')->firstOrFail();
        $this->assertSame($newTreasurer->id, $group['class']->fresh()->treasurer_id);
        $this->assertDatabaseHas('class_members', [
            'class_id' => $group['class']->id,
            'user_id' => $newTreasurer->id,
            'role_in_class' => 'bendahara',
        ]);
        $this->assertDatabaseHas('class_members', [
            'class_id' => $group['class']->id,
            'user_id' => $group['treasurer']->id,
            'role_in_class' => 'anggota',
        ]);

        $existing = User::factory()->create(['role' => 'bendahara']);
        $this->put('/walikelas/bendahara', ['user_id' => $existing->id])->assertRedirect('/walikelas/class');
        $this->assertSame($existing->id, $group['class']->fresh()->treasurer_id);
        $this->assertSame(1, $group['class']->members()->where('role_in_class', 'bendahara')->count());
    }

    public function test_treasurer_assigned_to_another_class_cannot_be_reused(): void
    {
        $first = $this->createCashClass('Kelas A');
        $second = $this->createCashClass('Kelas B');
        $this->actingAs($second['teacher'])->put('/walikelas/bendahara', ['user_id' => $first['treasurer']->id])->assertSessionHasErrors('user_id');
        $this->assertSame($second['treasurer']->id, $second['class']->fresh()->treasurer_id);
    }

    public function test_unassigned_accounts_have_no_fallback_access_to_existing_class(): void
    {
        $group = $this->createCashClass();
        app(TransactionService::class)->save($this->transactionData(['description' => 'Transaksi kelas privat']), $group['treasurer']->id);
        $stranger = User::factory()->create(['role' => 'siswa']);
        $this->assertNull(SchoolContext::forUser($stranger));
        $this->actingAs($stranger)->get('/student/transactions')->assertOk()->assertDontSee('Transaksi kelas privat');
        $this->post('/student/transactions', [])->assertSessionHasErrors('class');
        Sanctum::actingAs($stranger);
        $this->getJson('/api/v1/transactions')->assertOk()->assertJsonPath('data.pagination.total', 0);
    }

    public function test_transaction_reports_and_student_accounts_are_class_scoped(): void
    {
        $first = $this->createCashClass('Kelas A');
        $second = $this->createCashClass('Kelas B');
        $a = app(TransactionService::class)->save($this->transactionData(['amount' => 15000, 'description' => 'Privat kelas A']), $first['treasurer']->id);
        $b = app(TransactionService::class)->save($this->transactionData(['amount' => 90000, 'description' => 'Privat kelas B']), $second['treasurer']->id);
        $this->actingAs($first['treasurer'])->get('/transactions')->assertOk()->assertSee('Privat kelas A')->assertDontSee('Privat kelas B');
        $this->get('/transactions/'.$b->id)->assertNotFound();
        $this->delete('/transactions/'.$b->id)->assertNotFound();
        $this->get('/students/'.$second['student']->id)->assertNotFound();
        $this->get('/students')->assertOk()->assertSee($first['student']->name)->assertDontSee($second['student']->name);
        $this->get('/reports')->assertOk()->assertViewHas('totalIncome', 15000);
        $this->actingAs($first['teacher'])->get('/walikelas/reports')->assertOk()->assertViewHas('totalIncome', 15000)->assertDontSee('Rata-rata nilai');
    }

    public function test_second_treasurer_membership_is_rejected(): void
    {
        $group = $this->createCashClass();
        $other = User::factory()->create();
        $this->expectException(ValidationException::class);
        ClassMember::create(['class_id' => $group['class']->id, 'user_id' => $other->id, 'role_in_class' => 'bendahara']);
    }

    public function test_wali_creates_students_directly_in_their_class(): void
    {
        $group = $this->createCashClass();
        $this->actingAs($group['teacher'])->post('/walikelas/accounts', [
            'account_type' => 'siswa',
            'name' => 'Siswa Tambahan',
            'username' => 'siswa-tambahan',
            'email' => 'tambahan@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/walikelas/class');
        $user = User::where('username', 'siswa-tambahan')->firstOrFail();
        $this->assertSame('siswa', $user->role);
        $this->assertDatabaseHas('class_members', ['class_id' => $group['class']->id, 'user_id' => $user->id]);
        $this->actingAs($group['treasurer'])->post('/walikelas/accounts', [])->assertForbidden();
        $this->actingAs($group['treasurer'])->post('/students', [])->assertMethodNotAllowed();
    }

    public function test_class_account_page_combines_students_and_treasurer_and_links_to_account_form(): void
    {
        $group = $this->createCashClass();
        $this->actingAs($group['teacher'])->get('/walikelas/class')
            ->assertOk()
            ->assertSee('Bendahara')
            ->assertSee('Bendahara (siswa)')
            ->assertSee('2 siswa')
            ->assertSee($group['treasurer']->name)
            ->assertSee($group['student']->name)
            ->assertSee('Tambah akun')
            ->assertSee('/walikelas/accounts/create');

        $this->get('/walikelas/accounts/create')
            ->assertOk()
            ->assertSee('Jenis akun')
            ->assertSee('Siswa')
            ->assertSee('Bendahara');
    }

    public function test_wali_and_treasurer_can_manage_student_and_treasurer_accounts_from_student_page(): void
    {
        $group = $this->createCashClass();

        $this->actingAs($group['teacher'])->get('/students')
            ->assertOk()
            ->assertSee($group['student']->name)
            ->assertSee($group['treasurer']->name)
            ->assertSee('Edit')
            ->assertSee('Hapus');

        $this->put('/students/'.$group['student']->id, [
            'name' => 'Siswa Diperbarui',
            'username' => 'siswa-diperbarui',
            'email' => 'siswa-diperbarui@example.test',
            'phone' => '08123456789',
        ])->assertRedirect('/students');
        $this->assertDatabaseHas('users', [
            'id' => $group['student']->id,
            'name' => 'Siswa Diperbarui',
            'username' => 'siswa-diperbarui',
            'email' => 'siswa-diperbarui@example.test',
        ]);

        $this->actingAs($group['treasurer'])->put('/students/'.$group['student']->id, [
            'name' => 'Siswa Diperbarui Bendahara',
            'username' => 'siswa-diperbarui',
            'email' => 'siswa-diperbarui@example.test',
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
        ])->assertRedirect('/students');
        $this->assertDatabaseHas('users', [
            'id' => $group['student']->id,
            'name' => 'Siswa Diperbarui Bendahara',
        ]);

        $this->delete('/students/'.$group['student']->id)->assertRedirect('/students');
        $this->assertDatabaseMissing('users', ['id' => $group['student']->id]);

        $this->actingAs($group['teacher'])->delete('/students/'.$group['treasurer']->id)->assertRedirect('/students');
        $this->assertDatabaseMissing('users', ['id' => $group['treasurer']->id]);
        $this->assertNull($group['class']->fresh()->treasurer_id);
    }

    public function test_accounts_from_another_class_cannot_be_managed(): void
    {
        $first = $this->createCashClass('Kelas A');
        $second = $this->createCashClass('Kelas B');

        $this->actingAs($first['teacher'])
            ->get('/students/'.$second['student']->id.'/edit')
            ->assertNotFound();
        $this->put('/students/'.$second['student']->id, [
            'name' => 'Diambil Alih',
            'username' => 'diambil-alih',
            'email' => 'diambil-alih@example.test',
        ])->assertNotFound();
        $this->actingAs($first['treasurer'])
            ->delete('/students/'.$second['student']->id)
            ->assertNotFound();

        $this->assertDatabaseHas('users', [
            'id' => $second['student']->id,
            'name' => $second['student']->name,
        ]);
    }

    public function test_account_with_financial_history_and_own_account_cannot_be_deleted(): void
    {
        $group = $this->createCashClass();
        app(TransactionService::class)->save($this->transactionData(), $group['treasurer']->id);

        $this->actingAs($group['teacher'])
            ->delete('/students/'.$group['treasurer']->id)
            ->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $group['treasurer']->id]);

        $this->actingAs($group['treasurer'])
            ->delete('/students/'.$group['treasurer']->id)
            ->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $group['treasurer']->id]);
    }
}
