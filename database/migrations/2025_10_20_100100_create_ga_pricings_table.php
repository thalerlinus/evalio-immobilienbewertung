<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ga_pricings', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();   // "1_we", "2_3_we", "besichtigung", …
            $t->string('label');
            $t->unsignedInteger('price_eur')->nullable(); // null = auf Anfrage
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ga_pricings');
    }
};
