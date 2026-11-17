<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCurrentClass;
use Illuminate\Database\Eloquent\Model;

class LessonSchedule extends Model
{
    use BelongsToCurrentClass;

    protected $fillable = ['class_id', 'weekday', 'subject', 'teacher', 'start_time', 'end_time', 'room'];

    protected $casts = ['weekday' => 'integer'];

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }
}
