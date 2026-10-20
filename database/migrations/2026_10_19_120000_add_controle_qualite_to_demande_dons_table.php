<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Contrôle qualité : avis de l'IA vu par le donateur avant l'envoi, puis orientation décidée par l'association
     * à la réception (redistribution, boutique solidaire ou recyclage des fibres).
     */
    public function up(): void
    {
        Schema::table('demande_dons', function (Blueprint $table) {
            $table->string('controle_ia')->nullable()->after('message');
            $table->string('orientation')->nullable()->after('controle_ia');
            $table->text('orientation_motif')->nullable()->after('orientation');
        });
    }

    public function down(): void
    {
        Schema::table('demande_dons', function (Blueprint $table) {
            $table->dropColumn(['controle_ia', 'orientation', 'orientation_motif']);
        });
    }
};
