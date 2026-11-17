<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCurrentClass;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use BelongsToCurrentClass;
    use HasFactory;

    protected $fillable = [
        'class_id',
        'user_id',
        'title',
        'content',
        'is_pinned',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
    ];

    // Relasi ke class
    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    // Relasi ke user
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
