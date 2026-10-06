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
        Schema::create('reparations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demande_reparation_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('diagnostic')->nullable();
            $table->text('actions_realisees')->nullable();
            $table->text('pieces_utilisees')->nullable();
            $table->unsignedInteger('duree_reelle')->nullable();
            $table->string('photo_avant')->nullable();
            $table->string('photo_apres')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reparations');
    }
};
