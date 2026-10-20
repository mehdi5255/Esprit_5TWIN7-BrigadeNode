<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Aperçu généré par l'IA (vêtement une fois transformé), joint par le particulier comme référence pour l'atelier.
     */
    public function up(): void
    {
        Schema::table('demande_transformations', function (Blueprint $table) {
            $table->string('apercu_ia')->nullable()->after('inspiration_id');
        });
    }

    public function down(): void
    {
        Schema::table('demande_transformations', function (Blueprint $table) {
            $table->dropColumn('apercu_ia');
        });
    }
};
