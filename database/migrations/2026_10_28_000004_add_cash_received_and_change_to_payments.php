<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_submissions', function (Blueprint $table) {
            $table->decimal('cash_received', 15, 2)->nullable();
            $table->decimal('cash_change', 15, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('payment_submissions', fn (Blueprint $table) => $table->dropColumn(['cash_received', 'cash_change']));
    }
};
