<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCurrentClass;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use BelongsToCurrentClass;
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'color',
        'icon',
        'class_id',
    ];

    // Relasi ke class
    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    // Relasi ke transactions
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
