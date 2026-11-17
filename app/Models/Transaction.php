<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCurrentClass;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use BelongsToCurrentClass;
    use HasFactory;

    protected $table = 'transactions';

    protected $fillable = [
        'class_id',
        'user_id',
        'category_id',
        'type',
        'amount',
        'title',
        'description',
        'transaction_date',
        'proof_image',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    // Relasi ke user (yang input)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke class
    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    // Relasi ke category
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // Scope: filter by type
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // Scope: filter by date range
    public function scopeDateRange($query, $start, $end)
    {
        return $query->whereBetween('transaction_date', [$start, $end]);
    }

    // Accessor: format amount to Rupiah
    public function getFormattedAmountAttribute(): string
    {
        return 'Rp '.number_format($this->amount, 0, ',', '.');
    }

    public function setTransactionDateAttribute($value): void
    {
        $this->attributes['transaction_date'] = Carbon::parse($value)->toDateString();
    }
}
