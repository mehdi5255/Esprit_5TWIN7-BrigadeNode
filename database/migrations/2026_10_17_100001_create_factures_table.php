<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Émise quand la réparation est terminée : montant du devis + supplément éventuel justifié.
        Schema::create('factures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demande_reparation_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('montant_devis', 8, 2);
            $table->decimal('supplement', 8, 2)->default(0);
            $table->text('motif_supplement')->nullable();
            $table->decimal('montant_total', 8, 2);
            $table->string('statut')->default('emise');
            $table->dateTime('date_emission');
            $table->dateTime('date_paiement')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('factures');
    }
};
