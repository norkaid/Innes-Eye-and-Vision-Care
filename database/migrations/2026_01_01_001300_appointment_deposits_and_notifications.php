<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Products with a price are sold online by default.
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_purchasable')->default(true)->change();
        });
        DB::table('products')->update(['is_purchasable' => true]);

        Schema::table('appointments', function (Blueprint $table) {
            $table->decimal('consultation_fee', 10, 2)->nullable();   // full fee at the time of booking
            $table->unsignedTinyInteger('deposit_percent')->nullable(); // share paid online, e.g. 30
            $table->boolean('notify_email')->default(true);
            $table->boolean('notify_sms')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['consultation_fee', 'deposit_percent', 'notify_email', 'notify_sms']);
        });
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_purchasable')->default(false)->change();
        });
    }
};
