<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('renovation_categories', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();  // "baeder_wc", "dach_waermeschutz", …
            $t->string('label');
            $t->decimal('max_points', 4, 1); // 2.0, 4.0
            $t->timestamps();
        });

        // Zeitfenster-Faktoren je Kategorie (gar nicht, <=5, 5–10, …)
        Schema::create('renovation_time_factors', function (Blueprint $t) {
            $t->id();
            $t->foreignId('renovation_category_id')->constrained()->cascadeOnDelete();
            $t->string('time_window_key'); // "nicht","bis_5","bis_10","bis_15","bis_20","ueber_20","weiss_nicht"
            $t->decimal('factor', 5, 2);   // 1.00, 0.75, 0.50, …
            $t->unique(['renovation_category_id', 'time_window_key'], 'renov_time_cat_window_unique');
            $t->timestamps();
        });

        // Umfang-Gewichte (20/40/60/80/100 %)
        Schema::create('renovation_extent_weights', function (Blueprint $t) {
            $t->id();
            $t->unsignedTinyInteger('extent_percent'); // 20,40,60,80,100
            $t->decimal('weight', 4, 2);               // 0.20 … 1.00
            $t->unique('extent_percent');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('renovation_extent_weights');
        Schema::dropIfExists('renovation_time_factors');
        Schema::dropIfExists('renovation_categories');
    }
};
