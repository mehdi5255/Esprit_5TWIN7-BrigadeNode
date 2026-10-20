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
        // Composition d'un vêtement : table pivot N-N avec le pourcentage de chaque matière.
        Schema::create('matiere_vetement', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vetement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('matiere_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('pourcentage');
            $table->timestamps();
            $table->unique(['vetement_id', 'matiere_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matiere_vetement');
    }
};
