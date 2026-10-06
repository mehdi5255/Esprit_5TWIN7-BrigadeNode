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
        Schema::create('demande_transformations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vetement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('atelier_id')->constrained()->restrictOnDelete();
            $table->foreignId('particulier_id')->constrained('users')->cascadeOnDelete();
            $table->text('idee');
            $table->decimal('budget_max', 8, 2)->nullable();
            $table->dateTime('date_souhaitee')->nullable();

            // Workflow
            $table->string('statut')->default('en_attente');
            $table->text('motif_refus')->nullable();
            $table->dateTime('date_acceptation')->nullable();

            // Étape dépôt
            $table->dateTime('date_depot_effectif')->nullable();
            $table->boolean('depot_remis')->default(false);

            // Étape traitement
            $table->dateTime('date_debut_traitement')->nullable();
            $table->dateTime('date_fin_traitement')->nullable();
            $table->text('produit_final')->nullable();
            $table->string('photo_avant')->nullable();
            $table->string('photo_apres')->nullable();
            $table->decimal('cout_final', 8, 2)->nullable();

            // Étape récupération
            $table->dateTime('date_recuperation')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demande_transformations');
    }
};
