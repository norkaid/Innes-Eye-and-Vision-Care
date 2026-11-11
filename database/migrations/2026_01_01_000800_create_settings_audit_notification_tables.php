<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 100)->index();
            $table->string('entity_type', 100)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->nullable()->index();
            $table->index(['entity_type', 'entity_id']);
        });

        // Delivery outcomes for outbound notifications. No message bodies are stored.
        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('notification_type', 100);
            $table->string('channel', 30);
            $table->string('notifiable_type', 100)->nullable();
            $table->unsignedBigInteger('notifiable_id')->nullable();
            $table->string('status', 20)->index(); // sent | failed | skipped
            $table->string('error', 500)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['notifiable_type', 'notifiable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_deliveries');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('business_settings');
    }
};
