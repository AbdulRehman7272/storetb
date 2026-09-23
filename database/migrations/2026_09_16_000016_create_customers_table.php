<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('mobile')->index();
            $table->string('secondary_mobile')->nullable();
            $table->string('email')->nullable()->index();
            $table->decimal('total_spent', 12, 2)->default(0);
            $table->unsignedInteger('orders_count')->default(0);
            $table->timestamp('last_order_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('tags')->nullable();
            $table->boolean('is_blocked')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
