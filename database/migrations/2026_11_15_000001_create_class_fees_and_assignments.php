<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 150);
            $table->decimal('amount', 15, 2);
            $table->date('starts_at');
            $table->date('due_date');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index(['class_id', 'due_date']);
        });

        Schema::create('class_fee_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_fee_id')->constrained('class_fees')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['class_fee_id', 'user_id']);
            $table->index(['class_id', 'user_id']);
        });

        Schema::table('payment_submissions', function (Blueprint $table) {
            $table->foreignId('class_fee_assignment_id')
                ->nullable()
                ->constrained('class_fee_assignments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payment_submissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('class_fee_assignment_id');
        });
        Schema::dropIfExists('class_fee_assignments');
        Schema::dropIfExists('class_fees');
    }
};
