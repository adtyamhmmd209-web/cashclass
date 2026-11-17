<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class ClassMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'user_id',
        'role_in_class',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $member) {
            if ($member->role_in_class === 'bendahara' && ClassModel::whereKey($member->class_id)->value('treasurer_id') !== $member->user_id) {
                throw ValidationException::withMessages(['role_in_class' => 'Bendahara kelas hanya dapat diisi oleh penanggung jawab kelas yang ditetapkan.']);
            }
        });
    }

    // Relasi ke user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke class
    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }
}
