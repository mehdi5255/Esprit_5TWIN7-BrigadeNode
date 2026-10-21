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
        // Modèle d'origine de la demande (vide = idée libre). Supprimer l'inspiration ne supprime pas la demande.
        Schema::table('demande_transformations', function (Blueprint $table) {
            $table->foreignId('inspiration_id')->nullable()->after('atelier_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('demande_transformations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('inspiration_id');
        });
    }
};
