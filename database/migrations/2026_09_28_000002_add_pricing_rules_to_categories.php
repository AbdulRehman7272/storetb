<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('margin_type', 12)->default('flat')->after('description');
            $table->decimal('margin_value', 12, 2)->default(0)->after('margin_type');
            $table->string('discount_type', 12)->default('percentage')->after('margin_value');
            $table->decimal('discount_value', 12, 2)->default(0)->after('discount_type');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['margin_type', 'margin_value', 'discount_type', 'discount_value']);
        });
    }
};
