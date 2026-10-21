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
        // Catalogue des matières textiles, avec leur impact de fabrication (ordres de grandeur par kg).
        Schema::create('matieres', function (Blueprint $table) {
            $table->id();
            $table->string('nom')->unique();
            $table->string('type');
            $table->json('abreviations')->nullable();      // CO, cotton, algodón… pour lire les étiquettes
            $table->decimal('co2_kg_par_kg', 6, 2);
            $table->unsignedInteger('eau_litres_par_kg');
            $table->text('entretien')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matieres');
    }
};
