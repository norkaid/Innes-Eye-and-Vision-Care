<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Messages shown in a signed-in customer's account (Laravel database notifications).
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        // A customer's preferred channels; used to pre-tick the booking form.
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('notify_email')->default(true);
            $table->boolean('notify_sms')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['notify_email', 'notify_sms']));
        Schema::dropIfExists('notifications');
    }
};
