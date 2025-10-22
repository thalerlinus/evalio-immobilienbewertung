<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ga_pricings', function (Blueprint $table) {
            $table->string('category')->default('package')->after('label');
            $table->unsignedInteger('sort_order')->default(0)->after('category');
        });
    }

    public function down(): void
    {
        Schema::table('ga_pricings', function (Blueprint $table) {
            $table->dropColumn(['category', 'sort_order']);
        });
    }
};
