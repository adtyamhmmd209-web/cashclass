<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday');
            $table->string('subject', 100);
            $table->string('teacher', 100)->nullable();
            $table->time('start_time');
            $table->time('end_time');
            $table->string('room', 100)->nullable();
            $table->timestamps();
        });
        Schema::create('student_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('subject', 100);
            $table->unsignedTinyInteger('semester');
            $table->decimal('knowledge', 5, 2);
            $table->decimal('skills', 5, 2);
            $table->timestamps();
            $table->unique(['class_id', 'user_id', 'subject', 'semester']);
        });
        Schema::create('student_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('status', 20);
            $table->string('notes')->nullable();
            $table->timestamps();
            $table->unique(['class_id', 'user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_attendances');
        Schema::dropIfExists('student_grades');
        Schema::dropIfExists('lesson_schedules');
    }
};
