<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassModel extends Model
{
    use HasFactory;

    protected $table = 'classes';

    protected $fillable = [
        'name',
        'description',
        'academic_year',
        'teacher_id',
        'treasurer_id',
        'current_balance',
        'is_active',
    ];

    protected $casts = [
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function treasurer()
    {
        return $this->belongsTo(User::class, 'treasurer_id');
    }

    public function paymentAccounts()
    {
        return $this->hasMany(PaymentAccount::class, 'class_id');
    }

    // Relasi ke teacher (user)
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    // Relasi ke members
    public function members()
    {
        return $this->hasMany(ClassMember::class, 'class_id');
    }

    // Relasi ke transactions
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'class_id');
    }

    // Relasi ke categories
    public function categories()
    {
        return $this->hasMany(Category::class, 'class_id');
    }

    public function fees()
    {
        return $this->hasMany(ClassFee::class, 'class_id');
    }
}
