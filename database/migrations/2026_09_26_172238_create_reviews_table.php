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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('particulier_id')->constrained('users')->cascadeOnDelete();
            $table->morphs('reviewable');                 // Atelier ou Association noté(e)
            $table->morphs('demande');                    // Demande terminée qui justifie l'avis
            $table->unsignedTinyInteger('note');
            $table->text('commentaire')->nullable();
            $table->text('reponse')->nullable();
            $table->dateTime('date_reponse')->nullable();
            $table->string('statut')->default('publie');
            $table->timestamps();

            $table->unique(['demande_type', 'demande_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
