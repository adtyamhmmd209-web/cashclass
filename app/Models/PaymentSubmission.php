<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCurrentClass;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class PaymentSubmission extends Model
{
    use BelongsToCurrentClass;

    protected $fillable = ['user_id', 'class_id', 'class_fee_assignment_id', 'payment_date', 'amount', 'description', 'proof_image', 'status', 'reviewed_by', 'transaction_id', 'method', 'payment_account_id', 'payment_provider', 'destination_number', 'destination_holder', 'submission_key', 'cash_received', 'cash_change'];

    protected $casts = ['payment_date' => 'date', 'amount' => 'decimal:2', 'cash_received' => 'decimal:2', 'cash_change' => 'decimal:2'];

    public function getMethodLabelAttribute(): string
    {
        return match ($this->method) {
            'bank' => 'Transfer bank', 'e_wallet' => 'E-wallet', 'cash' => 'Cash / tunai', default => 'Pembayaran lama'
        };
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function classFeeAssignment()
    {
        return $this->belongsTo(ClassFeeAssignment::class);
    }

    public function setPaymentDateAttribute($value): void
    {
        $this->attributes['payment_date'] = Carbon::parse($value)->toDateString();
    }
}
