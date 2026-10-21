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
        // Proposition de prix de l'atelier, à accepter par le particulier avant le dépôt.
        Schema::create('devis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demande_reparation_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('main_oeuvre', 8, 2);
            $table->unsignedSmallInteger('delai_jours')->nullable();
            $table->date('valide_jusqu_au');
            $table->string('statut')->default('propose');
            $table->dateTime('date_reponse')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devis');
    }
};
