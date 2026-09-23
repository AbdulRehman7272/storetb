<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('color')->nullable()->after('product_type');
            $table->string('swatch', 7)->nullable()->after('color');
            $table->string('size')->nullable()->after('swatch');
        });
    }

    public function down(): void
    {
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn(['color', 'swatch', 'size']));
    }
};
