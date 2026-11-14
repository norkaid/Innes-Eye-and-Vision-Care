<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Media library: every image uploaded through the admin (logo, product photos, ...).
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('folder', 40)->index();
            $table->string('path')->unique();
            $table->string('original_name');
            $table->string('alt_text', 255)->nullable();
            $table->string('mime_type', 100);
            $table->unsignedInteger('size_bytes');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
