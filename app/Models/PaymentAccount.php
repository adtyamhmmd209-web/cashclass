<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCurrentClass;
use Illuminate\Database\Eloquent\Model;

class PaymentAccount extends Model
{
    use BelongsToCurrentClass;

    protected $fillable = ['class_id', 'type', 'provider', 'account_number', 'account_holder', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }
}
