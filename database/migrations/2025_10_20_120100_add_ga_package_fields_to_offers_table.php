<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->string('ga_package_key')->nullable()->after('inspection_price_eur');
            $table->string('ga_package_label')->nullable()->after('ga_package_key');
            $table->unsignedInteger('ga_package_price_eur')->nullable()->after('ga_package_label');
        });
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn(['ga_package_key', 'ga_package_label', 'ga_package_price_eur']);
        });
    }
};
