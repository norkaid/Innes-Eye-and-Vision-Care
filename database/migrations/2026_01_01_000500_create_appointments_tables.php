<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('reference_code', 20)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->string('customer_name', 150);
            $table->string('customer_email')->index();
            $table->string('customer_phone', 30)->index();
            $table->date('requested_date')->index();
            $table->time('requested_time')->nullable();
            $table->dateTime('proposed_start_at')->nullable();
            $table->dateTime('confirmed_start_at')->nullable();
            $table->dateTime('confirmed_end_at')->nullable();
            $table->string('status', 30)->default('pending')->index();
            $table->string('customer_note', 500)->nullable();
            $table->text('staff_note')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('status_updated_at')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'status', 'confirmed_start_at']);
        });

        Schema::create('appointment_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->string('old_status', 30)->nullable();
            $table->string('new_status', 30);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('internal_note')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_status_histories');
        Schema::dropIfExists('appointments');
    }
};
