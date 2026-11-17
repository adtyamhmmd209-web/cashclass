<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class CashclassRepairTest extends TestCase
{
    use RefreshDatabase, \Tests\Concerns\CreatesCashClass;

    private function payload(array $changes = []): array
    {
        return array_replace(['date' => now()->toDateString(), 'type' => 'income', 'category' => 'Kas', 'description' => 'Pembayaran kas kelas', 'amount' => 15000], $changes);
    }

    public function test_all_existing_pages_render_for_their_roles(): void
    {
        $sets = [
            'bendahara' => ['/dashboard', '/transactions', '/transactions/create', '/payments', '/reports', '/students', '/settings', '/profile', '/error-reports'],
            'siswa' => ['/student/dashboard', '/student/transactions', '/student/transactions/create', '/student/schedule'],
            'wali_kelas' => ['/walikelas/dashboard', '/walikelas/class', '/walikelas/accounts/create', '/walikelas/reports', '/walikelas/announcements'],
        ];
        Storage::fake('local');
        foreach ($sets as $role => $pages) {
            $user = User::factory()->create(['role' => $role]);
            if ($role === 'wali_kelas') {
                \App\Models\ClassModel::create(['name' => 'XI RPL', 'academic_year' => '2026/2027', 'teacher_id' => $user->id]);
            }
            $this->actingAs($user);
            foreach ($pages as $page) {
                $this->get($page)->assertOk();
            }
        }
    }

    public function test_roles_cannot_open_other_roles_pages(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'siswa']))
            ->get('/dashboard')
            ->assertRedirect('/student/dashboard');
        $this->actingAs(User::factory()->create(['role' => 'wali_kelas']))
            ->get('/dashboard')
            ->assertRedirect('/walikelas/dashboard');

        foreach (['siswa', 'wali_kelas'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));
            $pages = ['/transactions', '/payments', '/reports', '/settings', '/error-reports'];
            if ($role === 'siswa') {
                $pages[] = '/students';
            }
            foreach ($pages as $page) {
                $this->get($page)->assertForbidden();
            }
        }
        $this->actingAs(User::factory()->create(['role' => 'wali_kelas']))
            ->get('/students')
            ->assertOk();
        $this->actingAs(User::factory()->create(['role' => 'wali_kelas']))
            ->get('/student/dashboard')
            ->assertForbidden();
    }

    public function test_guest_can_submit_an_error_and_receive_a_reference(): void
    {
        Storage::fake('local');
        $this->get('/error-reports/create')->assertOk();
        $response = $this->post('/error-reports', ['summary' => 'Login gagal', 'message' => 'Pesan error muncul setelah menekan masuk.', 'page' => 'https://example.test/login?token=secret']);
        $response->assertRedirect(route('error-reports.create'))->assertSessionHas('reference');
        $id = session('reference');
        Storage::disk('local')->assertExists('error-reports/'.$id.'.json');
        $data = json_decode(Storage::disk('local')->get('error-reports/'.$id.'.json'), true);
        $this->assertSame('/login', $data['page']);
        $this->assertSame('open', $data['status']);
    }

    public function test_invalid_error_report_is_not_saved(): void
    {
        Storage::fake('local');
        $this->postJson('/error-reports', ['summary' => '', 'message' => 'short', 'email' => 'invalid'])->assertUnprocessable()->assertJsonValidationErrors(['summary', 'message', 'email']);
        $this->assertCount(0, Storage::disk('local')->files('error-reports'));
    }

    public function test_failed_report_storage_does_not_claim_success(): void
    {
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('put')->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('local')->andReturn($disk);
        $this->postJson('/error-reports', ['summary' => 'Error', 'message' => 'Terjadi kesalahan saat login.'])->assertStatus(503)->assertJsonMissingPath('reference');
    }

    public function test_report_submission_is_rate_limited(): void
    {
        Storage::fake('local');
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/error-reports', ['summary' => 'Error', 'message' => 'Terjadi kesalahan saat login.'])->assertCreated();
        }
        $this->postJson('/error-reports', ['summary' => 'Error', 'message' => 'Terjadi kesalahan saat login.'])->assertStatus(429);
        $this->assertCount(5, Storage::disk('local')->files('error-reports'));
    }

    public function test_bendahara_can_read_and_resolve_error_reports(): void
    {
        Storage::fake('local');
        $result = $this->postJson('/error-reports', ['summary' => '<script>alert(1)</script>', 'message' => 'Gagal menyimpan transaksi kelas.'])->assertCreated();
        $id = $result->json('reference');
        $this->get('/error-reports')->assertRedirect('/login');
        $this->actingAs(User::factory()->create());
        $this->get('/error-reports')->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->patch('/error-reports/'.$id, ['status' => 'resolved'])->assertRedirect();
        $this->assertSame('resolved', json_decode(Storage::disk('local')->get('error-reports/'.$id.'.json'), true)['status']);
        $this->patch('/error-reports/'.Str::uuid(), ['status' => 'resolved'])->assertNotFound();
    }

    public function test_transaction_crud_and_balance_use_assigned_class(): void
    {
        $group = $this->createCashClass();
        $class = $group['class'];
        $this->actingAs($group['treasurer']);
        $this->post('/transactions', $this->payload())->assertRedirect('/transactions');
        $transaction = Transaction::firstOrFail();
        $this->assertSame($class->id, $transaction->class_id);
        $this->assertSame('15000.00', $class->fresh()->current_balance);
        $this->get('/transactions/'.$transaction->id)->assertOk()->assertSee('Pembayaran kas kelas');
        $this->get('/transactions/'.$transaction->id.'/edit')->assertOk()->assertSee('value="15000"', false);
        $this->put('/transactions/'.$transaction->id, $this->payload(['type' => 'expense', 'amount' => 5000]))->assertRedirect('/transactions');
        $this->assertSame('-5000.00', $class->fresh()->current_balance);
        $this->delete('/transactions/'.$transaction->id)->assertRedirect('/transactions');
        $this->assertDatabaseCount('transactions', 0);
        $this->assertSame('0.00', $class->fresh()->current_balance);
    }

    public function test_transaction_form_supports_formatted_rupiah_amounts(): void
    {
        $group = $this->createCashClass();
        $this->actingAs($group['treasurer']);

        $this->get('/transactions/create')
            ->assertOk()
            ->assertSee('inputmode="numeric" maxlength="11" pattern="[0-9.]*"', false);

        $this->post('/transactions', $this->payload(['amount' => '422556000']))
            ->assertRedirect('/transactions');

        $this->assertDatabaseHas('transactions', ['amount' => 422556000]);
    }

    public function test_failed_update_preserves_old_proof_and_removes_new_upload(): void
    {
        Storage::fake('public');
        $group = $this->createCashClass();
        $user = $group['treasurer'];
        $this->actingAs($user);
        $service = app(TransactionService::class);
        $transaction = $service->save($this->payload(), $user->id, UploadedFile::fake()->create('old.jpg', 5));
        $oldPath = $transaction->proof_image;
        Transaction::updating(function () {
            throw new \RuntimeException('Simulated update failure');
        });
        try {
            $service->save($this->payload(['amount' => 25000]), $user->id, UploadedFile::fake()->create('new.jpg', 5), $transaction);
            $this->fail('Expected update failure.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Simulated update failure', $e->getMessage());
        }
        Storage::disk('public')->assertExists($oldPath);
        $this->assertCount(1, Storage::disk('public')->allFiles('proofs'));
        $this->assertSame('15000.00', $transaction->fresh()->amount);
        $this->assertSame('15000.00', $transaction->class->fresh()->current_balance);
    }

    public function test_web_token_generation_and_revocation_work(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/api-token')->assertOk()->assertJsonPath('data.token_type', 'Bearer');
        $this->assertSame(1, $user->tokens()->count());
        $this->deleteJson('/api-token')->assertOk();
        $this->assertSame(0, $user->tokens()->count());
        $this->deleteJson('/api-token')->assertOk();
    }

    public function test_api_routes_authentication_crud_and_validation_work(): void
    {
        $this->getJson('/api/v1/transactions')->assertUnauthorized();
        $group = $this->createCashClass();
        Sanctum::actingAs($group['treasurer']);
        $id = $this->postJson('/api/v1/transactions', $this->payload())->assertCreated()->json('data.id');
        $this->getJson('/api/v1/transactions')->assertOk()->assertJsonPath('data.pagination.total', 1);
        $this->getJson('/api/v1/transactions/'.$id)->assertOk();
        $this->getJson('/api/v1/transactions?per_page=0')->assertUnprocessable();
        $this->putJson('/api/v1/transactions/'.$id, $this->payload(['amount' => 20000]))->assertOk();
        $this->getJson('/api/v1/dashboard/stats')->assertOk()->assertJsonPath('data.summary.total_income', 20000);
        $this->deleteJson('/api/v1/transactions/'.$id)->assertOk();
    }

    public function test_duplicate_transaction_is_rejected_before_insert(): void
    {
        $group = $this->createCashClass();
        Sanctum::actingAs($group['treasurer']);
        $payload = $this->payload();

        $this->postJson('/api/v1/transactions', $payload)->assertCreated();
        $this->postJson('/api/v1/transactions', $payload)->assertUnprocessable()->assertJsonValidationErrors(['description']);
        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_students_cannot_modify_finances_through_api(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'siswa']));
        $this->postJson('/api/v1/transactions', $this->payload())->assertForbidden();
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_reports_use_filtered_database_values_and_print_view(): void
    {
        $group = $this->createCashClass();
        $user = $group['treasurer'];
        $this->actingAs($user);
        $service = app(TransactionService::class);
        $service->save($this->payload(['date' => '2026-01-31']), $user->id);
        $service->save($this->payload(['date' => '2026-02-10', 'amount' => 90000]), $user->id);
        $this->actingAs($user)->get('/reports?start_date=2026-01-01&end_date=2026-01-31')->assertOk()->assertViewHas('totalIncome', 15000)->assertViewHas('balance', 15000);
        $this->get('/reports/export-pdf?start_date=2026-01-01&end_date=2026-01-31')->assertOk()->assertSee('15.000')->assertDontSee('90.000');
        $this->get('/reports?start_date=invalid&end_date=2026-01-01')->assertSessionHasErrors('start_date');
        $this->get('/reports?start_date=2026-02-01&end_date=2026-01-01')->assertSessionHasErrors('end_date');
        $this->get('/dashboard')->assertOk()->assertViewHas('totalIncome', 105000);
    }

    public function test_settings_persist_class_and_categories(): void
    {
        $group = $this->createCashClass('XI RPL');
        $this->actingAs($group['treasurer']);
        $this->post('/settings/categories', ['name' => 'ATK', 'type' => 'expense'])->assertRedirect();
        $category = Category::firstOrFail();
        $this->get('/settings')->assertOk()->assertSee('XI RPL')->assertSee('ATK');
        $this->delete('/settings/categories/'.$category->id)->assertRedirect();
        $this->assertDatabaseCount('categories', 0);
    }

    public function test_empty_username_registration_and_email_login_work(): void
    {
        $this->post('/register', [
            'name' => 'Nama Baru',
            'username' => '',
            'email' => 'baru@example.test',
            'class_name' => 'X RPL',
            'academic_year' => '2026/2027',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/walikelas/dashboard');
        $this->assertNotEmpty(User::where('email', 'baru@example.test')->firstOrFail()->username);
        $this->post('/logout');
        $this->post('/login', ['username' => '', 'email' => 'baru@example.test', 'password' => 'password'])->assertRedirect('/walikelas/dashboard');
        $this->assertAuthenticated();
    }
}
