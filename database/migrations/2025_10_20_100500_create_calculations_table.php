<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calculations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // optional eingeloggter User
            $t->foreignId('property_type_id')->nullable()->constrained()->nullOnDelete();
            $t->unsignedSmallInteger('gnd')->nullable();
            $t->unsignedSmallInteger('baujahr')->nullable();
            $t->unsignedSmallInteger('anschaffungsjahr')->nullable();
            $t->unsignedSmallInteger('steuerjahr')->nullable();
            $t->unsignedSmallInteger('ermittlungsjahr')->nullable();
            $t->unsignedSmallInteger('alter')->nullable();
            $t->decimal('score', 4, 1)->nullable();        // 0.0–20.0 (0,5 Schritte)
            $t->json('score_details')->nullable();         // pro Kategorie
            $t->json('inputs')->nullable();                // UI-Inputs (Dropdowns etc.)
            $t->json('result_debug')->nullable();          // a/b/c, Formeltyp, Zwischenschritte
            $t->decimal('rnd_years', 6, 2)->nullable();    // z. B. 28.40
            $t->unsignedSmallInteger('rnd_min')->nullable();
            $t->unsignedSmallInteger('rnd_max')->nullable();
            $t->decimal('afa_percent', 5, 2)->nullable();  // 3.33
            $t->string('recommendation')->nullable();
            $t->string('status')->default('draft');        // draft|finalized
            $t->string('public_ref')->unique();            // zum späteren Aufruf per Link
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calculations');
    }
};
