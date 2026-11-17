<?php

namespace Tests\Feature;

use App\Models\PaymentSubmission;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesCashClass;
use Tests\TestCase;

class CashChangeTest extends TestCase
{
    use CreatesCashClass, RefreshDatabase;

    private array $group;

    protected function setUp(): void
    {
        parent::setUp();
        $this->group = $this->createCashClass();
        $this->actingAs($this->group['treasurer']);
    }

    private function data(array $replace = []): array
    {
        return array_replace(['user_id' => $this->group['student']->id, 'date' => now()->toDateString(), 'amount' => 20000, 'cash_received' => 50000, 'description' => 'Kas tunai', 'submission_key' => (string) Str::uuid()], $replace);
    }

    public function test_change_is_calculated_server_side_and_only_payment_amount_enters_cash(): void
    {
        $this->post('/payments/cash', $this->data(['cash_change' => 999999]))->assertRedirect('/payments');
        $payment = PaymentSubmission::firstOrFail();
        $this->assertSame('50000.00', $payment->cash_received);
        $this->assertSame('30000.00', $payment->cash_change);
        $this->assertSame('20000.00', Transaction::firstOrFail()->amount);
        $this->assertSame('20000.00', $this->group['class']->fresh()->current_balance);
        $this->get('/payments')->assertOk()->assertSee('Kembalian: Rp 30.000,00');
        $this->actingAs($this->group['student'])->get('/student/transactions')->assertOk()->assertSee('Kembalian: Rp 30.000,00');
    }

    public function test_exact_cash_payment_has_zero_change(): void
    {
        $this->post('/payments/cash', $this->data(['cash_received' => 20000]))->assertRedirect('/payments');
        $this->assertSame('0.00', PaymentSubmission::firstOrFail()->cash_change);
    }

    public function test_insufficient_or_missing_tender_is_rejected_without_ledger_entry(): void
    {
        $this->postJson('/payments/cash', $this->data(['cash_received' => 10000]))->assertUnprocessable()->assertJsonValidationErrors('cash_received');
        $this->postJson('/payments/cash', $this->data(['cash_received' => null]))->assertUnprocessable()->assertJsonValidationErrors('cash_received');
        $this->postJson('/payments/cash', $this->data(['cash_received' => -50000]))->assertUnprocessable()->assertJsonValidationErrors('cash_received');
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('payment_submissions', 0);
        $this->assertSame('0.00', $this->group['class']->fresh()->current_balance);
    }

    public function test_decimal_change_is_exact_and_more_than_two_decimal_places_are_rejected(): void
    {
        $this->post('/payments/cash', $this->data(['amount' => '15000.25', 'cash_received' => '20000.50']))->assertRedirect('/payments');
        $this->assertSame('5000.25', PaymentSubmission::firstOrFail()->cash_change);
        $this->assertSame('15000.25', $this->group['class']->fresh()->current_balance);
        $this->postJson('/payments/cash', $this->data(['cash_received' => '50000.001']))->assertUnprocessable()->assertJsonValidationErrors('cash_received');
        $this->assertDatabaseCount('transactions',1);
    }
}
