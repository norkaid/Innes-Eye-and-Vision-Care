<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Sold online through the shop (needs a price). Stock is optional: null = not tracked.
            $table->boolean('is_purchasable')->default(false)->index()->after('is_published');
            $table->unsignedInteger('stock_quantity')->nullable()->after('is_purchasable');
        });

        Schema::table('services', function (Blueprint $table) {
            // Fee paid online for an appointment request for this service. Null = no online payment.
            $table->decimal('booking_fee', 10, 2)->nullable()->after('preparation');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->string('payment_status', 20)->default('not_required')->index();
            $table->decimal('amount_due', 10, 2)->nullable();
            $table->timestamp('paid_at')->nullable();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name', 150);
            $table->string('customer_email');
            $table->string('customer_phone', 30);
            $table->string('fulfilment', 20); // pickup | delivery
            $table->foreignId('pickup_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('delivery_address', 500)->nullable();
            $table->string('customer_note', 500)->nullable();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->string('currency', 3)->default('GHS');
            $table->string('status', 30)->default('pending_payment')->index();
            $table->string('payment_status', 20)->default('unpaid')->index();
            $table->timestamp('paid_at')->nullable()->index();
            $table->boolean('stock_issue')->default(false);
            $table->text('staff_note')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('created_at');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name', 150);
            $table->decimal('unit_price', 10, 2);
            $table->unsignedSmallInteger('quantity');
            $table->decimal('line_total', 10, 2);
            $table->timestamps();
        });

        // One row per Paystack transaction attempt. Card details never reach this system.
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->morphs('payable'); // Order or Appointment
            $table->unsignedInteger('amount_minor'); // pesewas
            $table->string('currency', 3)->default('GHS');
            $table->string('status', 20)->default('pending')->index(); // pending | success | failed | abandoned
            $table->string('channel', 30)->nullable(); // card, mobile_money, bank ...
            $table->string('gateway_transaction_id', 40)->nullable();
            $table->string('gateway_response', 255)->nullable();
            $table->string('customer_email');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::table('appointments', fn (Blueprint $t) => $t->dropColumn(['payment_status', 'amount_due', 'paid_at']));
        Schema::table('services', fn (Blueprint $t) => $t->dropColumn('booking_fee'));
        Schema::table('products', function (Blueprint $t) {
            $t->dropIndex(['is_purchasable']);
            $t->dropColumn(['is_purchasable', 'stock_quantity']);
        });
    }
};
