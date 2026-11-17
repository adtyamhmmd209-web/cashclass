<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCurrentClass;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class StudentAttendance extends Model
{
    use BelongsToCurrentClass;

    protected $fillable = ['class_id', 'user_id', 'date', 'status', 'notes'];

    protected $casts = ['date' => 'date'];

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function setDateAttribute($value): void
    {
        $this->attributes['date'] = Carbon::parse($value)->toDateString();
    }
}
