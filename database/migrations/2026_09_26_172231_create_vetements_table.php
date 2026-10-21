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
        Schema::create('vetements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('categorie_id')->constrained('categorie_vetements')->restrictOnDelete();
            $table->string('nom');
            $table->string('matiere')->nullable();
            $table->string('taille')->nullable();
            $table->string('couleur')->nullable();
            $table->string('etat');
            $table->text('description')->nullable();
            $table->string('photo')->nullable();
            $table->string('statut')->default('disponible');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vetements');
    }
};
