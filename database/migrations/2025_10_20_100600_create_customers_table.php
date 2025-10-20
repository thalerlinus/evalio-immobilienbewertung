<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // falls registrierter Kunde
            $t->string('name')->nullable();
            $t->string('email')->nullable()->index();
            $t->string('phone')->nullable();
            // Rechnungsadresse
            $t->string('billing_street')->nullable();
            $t->string('billing_zip')->nullable();
            $t->string('billing_city')->nullable();
            $t->string('billing_country')->nullable()->default('DE');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
