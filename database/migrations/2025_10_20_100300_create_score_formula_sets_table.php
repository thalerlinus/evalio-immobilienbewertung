<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('score_formula_sets', function (Blueprint $t) {
            $t->id();
            $t->decimal('score', 4, 1)->unique();      // 0.0 … 20.0 in 0,5 Schritten
            $t->decimal('a', 8, 4)->nullable();
            $t->decimal('b', 8, 4)->nullable();
            $t->decimal('c', 8, 4)->nullable();
            $t->unsignedSmallInteger('alter_schwelle')->default(25); // z. B. 25 J.
            $t->decimal('rel_alter_min', 5, 2)->nullable();          // z. B. 0.60
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('score_formula_sets');
    }
};
