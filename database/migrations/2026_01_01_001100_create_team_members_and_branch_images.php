<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Team / practitioner profiles for the About page. Publish only with each
        // person's consent and with verified qualifications.
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 170)->unique();
            $table->string('job_title', 150)->nullable();
            $table->string('qualifications', 255)->nullable();
            $table->text('bio')->nullable();
            $table->string('image_path')->nullable();
            $table->boolean('is_published')->default(false)->index();
            $table->unsignedInteger('display_order')->default(0)->index();
            $table->timestamps();
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
        Schema::dropIfExists('team_members');
    }
};
