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
        Schema::create('associations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('responsable_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('nom');
            $table->string('domaine_action')->nullable();
            $table->text('description')->nullable();
            $table->string('adresse')->nullable();
            $table->string('code_postal', 10)->nullable();
            $table->string('ville')->nullable();
            $table->string('telephone')->nullable();
            $table->string('email')->nullable();
            $table->string('logo')->nullable();
            $table->string('statut')->default('en_attente');
            $table->timestamps();
        });

        Schema::create('besoin_associations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('association_id')->constrained()->cascadeOnDelete();
            $table->foreignId('categorie_id')->nullable()->constrained('categorie_vetements')->nullOnDelete();
            $table->string('titre');
            $table->text('description')->nullable();
            $table->unsignedInteger('quantite');
            $table->string('priorite')->default('moyenne');
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        Schema::create('demande_dons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vetement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('association_id')->constrained()->restrictOnDelete();
            $table->foreignId('besoin_id')->nullable()->constrained('besoin_associations')->nullOnDelete();
            $table->foreignId('particulier_id')->constrained('users')->cascadeOnDelete();
            $table->text('message')->nullable();

            // Workflow
            $table->string('statut')->default('en_attente');
            $table->text('motif_refus')->nullable();
            $table->dateTime('date_acceptation')->nullable();

            // Étape dépôt
            $table->dateTime('date_depot_effectif')->nullable();
            $table->boolean('depot_remis')->default(false);

            // Étape préparation / remise
            $table->dateTime('date_debut_traitement')->nullable();
            $table->dateTime('date_remise')->nullable();
            $table->text('beneficiaire_info')->nullable();
            $table->unsignedInteger('nombre_beneficiaires')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demande_dons');
        Schema::dropIfExists('besoin_associations');
        Schema::dropIfExists('associations');
    }
};
