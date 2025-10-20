<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $t) {
            $t->id();
            $t->string('number')->unique();                 // laufende Angebots-Nr.
            $t->foreignId('calculation_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('property_type_id')->nullable()->constrained()->nullOnDelete();

            // Snapshot der Berechnung & Eingaben, damit das Angebot „eingefroren" bleibt
            $t->json('calculation_snapshot')->nullable();
            $t->json('input_snapshot')->nullable();

            // Preiszusammenfassung (Basis + Addons)
            $t->unsignedInteger('base_price_eur')->nullable();    // z.B. nach Immobilientyp
            $t->unsignedInteger('inspection_price_eur')->nullable(); // Besichtigung/Online
            $t->unsignedInteger('discount_eur')->default(0);
            $t->unsignedInteger('net_total_eur')->nullable();
            $t->unsignedInteger('vat_percent')->default(19);
            $t->unsignedInteger('vat_amount_eur')->nullable();
            $t->unsignedInteger('gross_total_eur')->nullable();

            // Status & Freigaben
            $t->enum('status', ['draft', 'sent', 'accepted', 'rejected', 'expired'])->default('draft');
            $t->timestamp('sent_at')->nullable();
            $t->timestamp('accepted_at')->nullable();
            $t->timestamp('rejected_at')->nullable();
            $t->timestamp('expires_at')->nullable();

            // Public Zugriff (Angebots-Link für Kunden)
            $t->string('view_token', 64)->unique();
            $t->json('line_items')->nullable(); // optional: Positionen (GA, Besichtigung, Online, Sonderposten)
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        // (Optional) Dateien am Angebot (PDF, Anhänge)
        Schema::create('offer_attachments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $t->string('disk')->default('public');
            $t->string('path');                 // Storage-Pfad
            $t->string('original_name')->nullable();
            $t->unsignedInteger('size_bytes')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_attachments');
        Schema::dropIfExists('offers');
    }
};
