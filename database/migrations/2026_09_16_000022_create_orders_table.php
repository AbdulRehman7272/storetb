<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('shipping_method_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name');
            $table->string('mobile')->index();
            $table->string('secondary_mobile')->nullable();
            $table->string('email')->nullable();
            $table->string('province');
            $table->string('city')->index();
            $table->text('address');
            $table->string('landmark')->nullable();
            $table->text('customer_notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('shipping_total', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->string('status')->default('new')->index();
            $table->string('payment_status')->default('unpaid')->index();
            $table->string('payment_method')->default('cod')->index();
            $table->foreignId('payment_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('courier')->nullable();
            $table->string('tracking_number')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->string('return_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
