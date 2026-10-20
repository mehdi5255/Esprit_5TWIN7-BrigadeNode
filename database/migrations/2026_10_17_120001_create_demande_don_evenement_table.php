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
        // Table pivot N-N : un don passe par sa collecte puis sa distribution ; un événement regroupe plusieurs dons.
        Schema::create('demande_don_evenement', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demande_don_id')->constrained()->cascadeOnDelete();
            $table->foreignId('evenement_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['demande_don_id', 'evenement_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demande_don_evenement');
    }
};
