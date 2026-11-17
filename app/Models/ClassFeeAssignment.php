<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCurrentClass;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassFeeAssignment extends Model
{
    use BelongsToCurrentClass;

    protected $fillable = [
        'class_fee_id',
        'class_id',
        'user_id',
    ];

    public function classFee(): BelongsTo
    {
        return $this->belongsTo(ClassFee::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PaymentSubmission::class);
    }
}
