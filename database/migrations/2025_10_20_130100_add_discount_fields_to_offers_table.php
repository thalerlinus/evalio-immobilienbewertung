<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->string('discount_code')->nullable()->after('inspection_price_eur');
            $table->unsignedTinyInteger('discount_percent')->nullable()->after('discount_code');
            $table->timestamp('discount_applied_at')->nullable()->after('discount_percent');
        });
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn(['discount_code', 'discount_percent', 'discount_applied_at']);
        });
    }
};
