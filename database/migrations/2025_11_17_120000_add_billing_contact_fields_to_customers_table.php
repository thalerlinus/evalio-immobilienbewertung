<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('billing_name')->nullable()->after('billing_country');
            $table->string('billing_company')->nullable()->after('billing_name');
            $table->string('billing_email')->nullable()->after('billing_company');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'billing_name',
                'billing_company',
                'billing_email',
            ]);
        });
    }
};
