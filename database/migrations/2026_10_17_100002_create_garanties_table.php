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
        // Créée au paiement de la facture, qui sert de preuve d'achat.
        Schema::create('garanties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facture_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('duree_mois');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->text('conditions');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('garanties');
    }
};
