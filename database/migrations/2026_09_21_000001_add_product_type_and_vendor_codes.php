<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('product_type')->default('single')->after('name');
            $table->string('vendor_reference')->nullable()->after('sku');
            $table->string('vendor_code')->nullable()->unique()->after('vendor_reference');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('vendor_reference')->nullable()->after('sku');
            $table->string('vendor_code')->nullable()->unique()->after('vendor_reference');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', fn (Blueprint $table) => $table->dropColumn(['vendor_reference', 'vendor_code']));
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn(['product_type', 'vendor_reference', 'vendor_code']));
    }
};
