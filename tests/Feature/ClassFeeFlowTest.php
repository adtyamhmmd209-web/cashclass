<?php

namespace Tests\Feature;

use App\Models\ClassFee;
use App\Models\ClassMember;
use App\Models\PaymentSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesCashClass;
use Tests\TestCase;

class ClassFeeFlowTest extends TestCase
{
    use CreatesCashClass, RefreshDatabase;

    private array $group;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->group = $this->createCashClass();
    }

    private function createFee(array $replace = []): ClassFee
    {
        $data = array_replace([
            'title' => 'Kas November',
            'amount' => 15000,
            'starts_at' => today()->toDateString(),
            'due_date' => today()->addDays(7)->toDateString(),
            'description' => 'Iuran kelas periode November',
        ], $replace);

        $this->actingAs($this->group['treasurer'])->post('/class-fees', $data)->assertRedirect('/class-fees');

        return ClassFee::where('class_id', $this->group['class']->id)->latest('id')->firstOrFail();
    }

    public function test_new_fee_is_assigned_only_to_students_enrolled_at_issue_time(): void
    {
        $fee = $this->createFee();
        $newStudent = User::factory()->create(['role' => 'siswa']);
        ClassMember::create(['class_id' => $this->group['class']->id, 'user_id' => $newStudent->id, 'role_in_class' => 'anggota']);

        $this->assertSame(1, $fee->assignments()->count());
        $this->assertSame($this->group['student']->id, $fee->assignments()->firstOrFail()->user_id);
        $this->actingAs($newStudent)->get('/student/fees')->assertOk()->assertSee('Belum ada tagihan');
        $this->actingAs($this->group['student'])->get('/student/fees')->assertOk()->assertSee('Kas November')->assertSee('Belum dibayar');
    }

    public function test_transfer_submission_is_linked_to_fee_and_cannot_be_submitted_twice_while_pending(): void
    {
        $fee = $this->createFee();
        $assignment = $fee->assignments()->firstOrFail();
        $data = [
            'date' => today()->toDateString(),
            'amount' => 15000,
            'description' => 'Kas November',
            'method' => 'bank',
            'payment_account_id' => $this->group['bank']->id,
            'class_fee_assignment_id' => $assignment->id,
            'proof' => $this->proofFile(),
            'submission_key' => (string) Str::uuid(),
        ];

        $this->actingAs($this->group['student'])->post('/student/transactions', $data)->assertRedirect('/student/transactions');
        $payment = PaymentSubmission::firstOrFail();
        $this->assertSame($assignment->id, $payment->class_fee_assignment_id);
        $this->post('/student/transactions', array_replace($data, ['submission_key' => (string) Str::uuid(), 'proof' => $this->proofFile()]))
            ->assertSessionHasErrors('class_fee_assignment_id');

        $this->actingAs($this->group['treasurer'])->patch('/payments/'.$payment->id, ['status' => 'rejected'])->assertRedirect();
        $this->actingAs($this->group['student'])->get('/student/fees')->assertOk()->assertSee('Pembayaran ditolak');
        $this->post('/student/transactions', array_replace($data, ['submission_key' => (string) Str::uuid(), 'proof' => $this->proofFile()]))
            ->assertRedirect('/student/transactions');
        $retry = PaymentSubmission::latest('id')->firstOrFail();
        $this->actingAs($this->group['treasurer'])->patch('/payments/'.$retry->id, ['status' => 'approved'])->assertRedirect();
        $this->actingAs($this->group['student'])->get('/student/fees')->assertOk()->assertSee('Lunas');
        $this->assertSame('rejected', $payment->fresh()->status);
        $this->assertSame('approved', $retry->fresh()->status);
    }

    public function test_linked_payment_must_match_fee_amount_and_cash_payment_marks_fee_paid(): void
    {
        $fee = $this->createFee();
        $assignment = $fee->assignments()->firstOrFail();
        $data = [
            'date' => today()->toDateString(),
            'amount' => 14000,
            'description' => 'Kas November',
            'method' => 'bank',
            'payment_account_id' => $this->group['bank']->id,
            'class_fee_assignment_id' => $assignment->id,
            'proof' => $this->proofFile(),
            'submission_key' => (string) Str::uuid(),
        ];
        $this->actingAs($this->group['student'])->post('/student/transactions', $data)->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('payment_submissions', 0);

        $cashData = [
            'user_id' => $this->group['student']->id,
            'class_fee_assignment_id' => $assignment->id,
            'date' => today()->toDateString(),
            'amount' => 15000,
            'cash_received' => 20000,
            'description' => 'Kas November',
            'submission_key' => (string) Str::uuid(),
        ];
        $this->actingAs($this->group['treasurer'])->post('/payments/cash', $cashData)->assertRedirect('/payments');
        $payment = PaymentSubmission::firstOrFail();
        $this->assertSame($assignment->id, $payment->class_fee_assignment_id);
        $this->assertSame('approved', $payment->status);
        $this->actingAs($this->group['student'])->get('/student/fees')->assertOk()->assertSee('Lunas');
    }

    public function test_wali_dashboard_shows_only_overdue_unpaid_fees(): void
    {
        $this->createFee(['title' => 'Terlambat', 'starts_at' => today()->subWeek()->toDateString(), 'due_date' => today()->subDay()->toDateString()]);
        $this->createFee(['title' => 'Jatuh tempo hari ini', 'amount' => 20000, 'due_date' => today()->toDateString()]);

        $this->actingAs($this->group['teacher'])->get('/walikelas/dashboard')
            ->assertOk()
            ->assertSee('Tunggakan jatuh tempo')
            ->assertSee('15.000')
            ->assertSee('35.000');
    }

    public function test_fee_with_payment_history_cannot_be_changed_or_deleted(): void
    {
        $fee = $this->createFee();
        $this->actingAs($this->group['student'])->post('/student/transactions', [
            'date' => today()->toDateString(),
            'amount' => 15000,
            'description' => 'Kas November',
            'method' => 'bank',
            'payment_account_id' => $this->group['bank']->id,
            'class_fee_assignment_id' => $fee->assignments()->firstOrFail()->id,
            'proof' => $this->proofFile(),
            'submission_key' => (string) Str::uuid(),
        ])->assertRedirect();

        $this->actingAs($this->group['treasurer'])->put('/class-fees/'.$fee->id, [
            'title' => 'Nama baru',
            'amount' => 15000,
            'starts_at' => today()->toDateString(),
            'due_date' => today()->addDays(7)->toDateString(),
        ])->assertSessionHasErrors('fee');
        $this->delete('/class-fees/'.$fee->id)->assertStatus(409);
    }
}
