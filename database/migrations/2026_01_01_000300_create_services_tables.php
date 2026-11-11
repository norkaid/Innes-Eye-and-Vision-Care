<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 170)->unique();
            $table->string('short_description', 300)->nullable();
            $table->text('description')->nullable();
            $table->text('preparation')->nullable();
            $table->string('image_path')->nullable();
            $table->boolean('is_published')->default(false)->index();
            $table->unsignedInteger('display_order')->default(0)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('branch_service', function (Blueprint $table) {
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['branch_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_service');
        Schema::dropIfExists('services');
    }
};
