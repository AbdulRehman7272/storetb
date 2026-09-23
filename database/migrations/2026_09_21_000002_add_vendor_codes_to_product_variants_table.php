<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('vendor_reference')->nullable()->after('sku');
            $table->string('vendor_code')->nullable()->unique()->after('vendor_reference');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', fn (Blueprint $table) => $table->dropColumn(['vendor_reference', 'vendor_code']));
    }
};
