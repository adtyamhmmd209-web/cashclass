<?php

namespace Tests\Feature;

use App\Models\PaymentAccount;
use App\Models\PaymentSubmission;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesCashClass;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use CreatesCashClass, RefreshDatabase;

    private array $group;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->group = $this->createCashClass();
    }

    private function transferData(array $replace = []): array
    {
        return array_replace(['date' => now()->toDateString(), 'amount' => 15000, 'description' => 'Kas Oktober', 'method' => 'bank', 'payment_account_id' => $this->group['bank']->id, 'proof' => $this->proofFile(), 'submission_key' => (string) Str::uuid()], $replace);
    }

    private function submit(array $replace = []): PaymentSubmission
    {
        $this->actingAs($this->group['student'])->post('/student/transactions', $this->transferData($replace))->assertRedirect('/student/transactions');

        return PaymentSubmission::latest('id')->firstOrFail();
    }

    public function test_bank_transfer_waits_for_review_and_is_credited_once(): void
    {
        $payment = $this->submit();
        $this->assertSame('pending', $payment->status);
        $this->assertSame('bank', $payment->method);
        $this->assertSame('1234567890', $payment->destination_number);
        $this->assertDatabaseCount('transactions', 0);
        $this->get('/student/transactions')->assertOk()->assertSee('Transfer bank')->assertSee('Menunggu verifikasi');
        $this->actingAs($this->group['treasurer'])->patch('/payments/'.$payment->id, ['status' => 'approved'])->assertRedirect();
        $this->assertSame('15000.00', $this->group['class']->fresh()->current_balance);
        $this->assertSame('approved', $payment->fresh()->status);
        $transaction = Transaction::firstOrFail();
        $this->assertSame($this->group['treasurer']->id, $transaction->user_id);
        $this->assertNotSame($payment->proof_image, $transaction->proof_image);
        Storage::disk('public')->assertExists($transaction->proof_image);
        $this->patch('/payments/'.$payment->id, ['status' => 'approved'])->assertStatus(409);
        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_wallet_transfer_and_destination_snapshot_are_preserved(): void
    {
        $payment = $this->submit(['method' => 'e_wallet', 'payment_account_id' => $this->group['wallet']->id]);
        $this->assertSame('e_wallet', $payment->method);
        $this->group['wallet']->update(['account_number' => '08999999999', 'account_holder' => 'Nama baru']);
        $this->assertSame('081234567890', $payment->fresh()->destination_number);
        $this->actingAs($this->group['treasurer'])->patch('/payments/'.$payment->id, ['status' => 'approved'])->assertRedirect();
        $this->assertSame('15000.00', $this->group['class']->fresh()->current_balance);
    }

    public function test_cash_is_recorded_immediately_by_treasurer_and_retries_do_not_duplicate(): void
    {
        $data = ['user_id' => $this->group['student']->id, 'date' => now()->toDateString(), 'amount' => 20000, 'cash_received' => 50000, 'description' => 'Kas tunai', 'submission_key' => (string) Str::uuid()];
        $this->actingAs($this->group['treasurer'])->post('/payments/cash', $data)->assertRedirect('/payments');
        $this->post('/payments/cash', $data)->assertRedirect('/payments');
        $this->assertDatabaseCount('transactions', 1);
        $this->assertDatabaseCount('payment_submissions', 1);
        $payment = PaymentSubmission::firstOrFail();
        $this->assertSame('cash', $payment->method);
        $this->assertSame('approved', $payment->status);
        $this->assertNull($payment->proof_image);
        $this->assertSame('50000.00', $payment->cash_received);
        $this->assertSame('30000.00', $payment->cash_change);
        $this->post('/payments/cash', array_replace($data, ['cash_received' => 60000]))->assertStatus(409);
        $this->assertSame('20000.00', $this->group['class']->fresh()->current_balance);
        $this->post('/payments/cash', array_replace($data, ['amount' => 30000]))->assertStatus(409);
        $this->actingAs($this->group['student'])->get('/student/transactions')->assertOk()->assertSee('Cash / tunai')->assertSee('20.000');
    }

    public function test_students_cannot_record_cash_or_submit_cash_as_transfer(): void
    {
        $this->actingAs($this->group['student'])->post('/payments/cash', [])->assertForbidden();
        $this->post('/student/transactions', $this->transferData(['method' => 'cash']))->assertSessionHasErrors('method');
        $this->assertDatabaseCount('payment_submissions', 0);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_proof_is_required_and_account_must_match_the_selected_channel(): void
    {
        $this->actingAs($this->group['student'])->post('/student/transactions', $this->transferData(['proof' => null]))->assertSessionHasErrors('proof');
        $this->post('/student/transactions', $this->transferData(['method' => 'e_wallet']))->assertSessionHasErrors('payment_account_id');
        $this->assertDatabaseCount('payment_submissions', 0);
    }

    public function test_inactive_and_other_class_destinations_cannot_be_used(): void
    {
        $other = $this->createCashClass('Kelas lain');
        $this->actingAs($this->group['student'])->post('/student/transactions', $this->transferData(['payment_account_id' => $other['bank']->id]))->assertSessionHasErrors('payment_account_id');
        $this->group['bank']->update(['is_active' => false]);
        $this->post('/student/transactions', $this->transferData())->assertSessionHasErrors('payment_account_id');
        $this->assertDatabaseCount('payment_submissions', 0);
    }

    public function test_repeat_transfer_submission_is_not_duplicated(): void
    {
        $data = $this->transferData();
        $this->actingAs($this->group['student'])->post('/student/transactions', $data)->assertRedirect();
        $this->post('/student/transactions', $data)->assertSessionHasErrors('submission_key');
        $this->assertDatabaseCount('payment_submissions', 1);
        $this->assertCount(1, Storage::disk('public')->allFiles('payment-proofs'));
    }

    public function test_another_classes_treasurer_cannot_view_or_approve_payment(): void
    {
        $payment = $this->submit();
        $other = $this->createCashClass('Kelas lain');
        $this->actingAs($other['treasurer'])->get('/payments')->assertOk()->assertDontSee('Kas Oktober');
        $this->patch('/payments/'.$payment->id, ['status' => 'approved'])->assertNotFound();
        $this->get('/payments/'.$payment->id.'/proof')->assertNotFound();
        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_rejection_and_failed_proof_copy_do_not_change_balance(): void
    {
        $payment = $this->submit();
        Storage::disk('public')->delete($payment->proof_image);
        $this->actingAs($this->group['treasurer'])->patch('/payments/'.$payment->id, ['status' => 'approved'])->assertSessionHas('error');
        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertDatabaseCount('transactions', 0);
        $this->assertSame('0.00', $this->group['class']->fresh()->current_balance);
        $this->patch('/payments/'.$payment->id, ['status' => 'rejected'])->assertRedirect();
        $this->assertSame('rejected', $payment->fresh()->status);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_other_students_cannot_read_proofs_and_cash_student_must_be_in_class(): void
    {
        $payment = $this->submit();
        $other = $this->createCashClass('Kelas lain');
        $this->actingAs($other['student'])->get('/student/payments/'.$payment->id.'/proof')->assertNotFound();
        $this->actingAs($this->group['treasurer'])->post('/payments/cash', ['user_id' => $other['student']->id, 'date' => now()->toDateString(), 'amount' => 15000, 'description' => 'Cash', 'submission_key' => (string) Str::uuid()])->assertSessionHasErrors('user_id');
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_treasurer_can_save_disable_and_deduplicate_destinations(): void
    {
        $data = ['type' => 'bank', 'provider' => 'bank baru', 'account_number' => '123 456 789', 'account_holder' => 'Kas Kelas'];
        $this->actingAs($this->group['treasurer'])->post('/settings/payment-accounts', $data)->assertRedirect();
        $this->post('/settings/payment-accounts', array_replace($data, ['provider' => 'BANK BARU']))->assertRedirect();
        $account = PaymentAccount::where('provider', 'BANK BARU')->firstOrFail();
        $this->assertSame('123456789', $account->account_number);
        $this->assertSame(1, PaymentAccount::where('provider', 'BANK BARU')->count());
        $this->patch('/settings/payment-accounts/'.$account->id, ['is_active' => false])->assertRedirect();
        $this->assertFalse($account->fresh()->is_active);
    }
}
