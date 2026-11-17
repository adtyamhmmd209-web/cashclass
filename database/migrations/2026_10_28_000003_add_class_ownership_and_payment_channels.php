<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->foreignId('treasurer_id')->nullable()->constrained('users')->restrictOnDelete();
        });
        Schema::create('payment_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('provider', 80);
            $table->string('account_number', 50);
            $table->string('account_holder', 100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['class_id', 'type', 'provider', 'account_number'], 'payment_account_destination_unique');
        });
        Schema::table('payment_submissions', function (Blueprint $table) {
            $table->string('method', 20)->nullable();
            $table->foreignId('payment_account_id')->nullable()->constrained('payment_accounts')->nullOnDelete();
            $table->string('payment_provider', 80)->nullable();
            $table->string('destination_number', 50)->nullable();
            $table->string('destination_holder', 100)->nullable();
            $table->uuid('submission_key')->nullable()->unique();
        });
        // Use explicit class membership first; never attach an unrelated class at runtime.
        $classes = DB::table('classes')->orderBy('id')->get();
        foreach ($classes as $class) {
            $treasurer = DB::table('class_members')->join('users', 'users.id', '=', 'class_members.user_id')
                ->where('class_members.class_id', $class->id)->where('class_members.is_active', true)
                ->where('class_members.role_in_class', 'bendahara')->where('users.role', 'bendahara')
                ->orderBy('class_members.id')->value('users.id');
            if (! $treasurer) {
                $authors = DB::table('transactions')->join('users', 'users.id', '=', 'transactions.user_id')->where('transactions.class_id', $class->id)->where('users.role', 'bendahara')->distinct()->pluck('users.id');
                if ($authors->count() === 1) {
                    $treasurer = $authors->first();
                }
            }
            if (! $treasurer && $classes->count() === 1 && DB::table('users')->where('role', 'bendahara')->count() === 1) {
                $treasurer = DB::table('users')->where('role', 'bendahara')->value('id');
            }
            $updates = ['treasurer_id' => $treasurer];
            if (! $class->teacher_id && $classes->count() === 1 && DB::table('users')->where('role', 'wali_kelas')->count() === 1) {
                $updates['teacher_id'] = DB::table('users')->where('role', 'wali_kelas')->value('id');
            }
            DB::table('classes')->where('id', $class->id)->update($updates);
            if ($treasurer) {
                DB::table('class_members')->where('class_id', $class->id)->where('role_in_class', 'bendahara')->where('user_id', '!=', $treasurer)->update(['role_in_class' => 'anggota']);
                DB::table('class_members')->updateOrInsert(['class_id' => $class->id, 'user_id' => $treasurer], ['role_in_class' => 'bendahara', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('payment_submissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_account_id');
            $table->dropUnique(['submission_key']);
            $table->dropColumn(['method', 'payment_provider', 'destination_number', 'destination_holder', 'submission_key']);
        });
        Schema::dropIfExists('payment_accounts');
        Schema::table('classes', fn (Blueprint $table) => $table->dropConstrainedForeignId('treasurer_id'));
    }
};
