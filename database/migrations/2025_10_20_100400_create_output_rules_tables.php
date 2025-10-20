<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interval_rules', function (Blueprint $t) {
            $t->id();
            $t->enum('method', ['percent', 'class'])->default('percent');
            $t->decimal('param1', 6, 3)->nullable(); // z.B. 0.10 = ±10 %
            $t->decimal('param2', 6, 3)->nullable(); // optional
            $t->string('note')->nullable();
            $t->timestamps();
        });

        Schema::create('recommendation_rules', function (Blueprint $t) {
            $t->id();
            $t->string('threshold_logic'); // z.B. ">=25"
            $t->string('text');           // „Gutachten ist sinnvoll …"
            $t->unsignedInteger('sort')->default(0);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendation_rules');
        Schema::dropIfExists('interval_rules');
    }
};
