<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCurrentClass;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassFee extends Model
{
    use BelongsToCurrentClass;

    protected $fillable = [
        'class_id',
        'created_by',
        'title',
        'amount',
        'starts_at',
        'due_date',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'starts_at' => 'date',
        'due_date' => 'date',
    ];

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ClassFeeAssignment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
