<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_types', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();                  // z.B. "dfh", "mfh_4_10"
            $t->string('label');                          // "Zweifamilienhaus" …
            $t->unsignedSmallInteger('gnd')->nullable()->default(80); // Gesamtnutzungsdauer
            $t->boolean('request_only')->default(false);  // "auf Anfrage"
            $t->unsignedInteger('price_standard_eur')->nullable(); // 1549 etc.
            $t->text('notes')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_types');
    }
};
