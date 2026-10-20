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
        // Modèles d'upcycling publiés par les ateliers, proposés aux particuliers.
        Schema::create('inspirations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('atelier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('categorie_id')->constrained('categorie_vetements')->restrictOnDelete();
            $table->string('titre');
            $table->text('description');
            $table->string('photo')->nullable();
            $table->decimal('prix_indicatif', 8, 2)->nullable();
            $table->unsignedSmallInteger('delai_jours')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inspirations');
    }
};
