<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Photo de l'abîmement prise par le particulier (analysée par le pré-diagnostic IA), visible par l'atelier.
     */
    public function up(): void
    {
        Schema::table('demande_reparations', function (Blueprint $table) {
            $table->string('photo_probleme')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('demande_reparations', function (Blueprint $table) {
            $table->dropColumn('photo_probleme');
        });
    }
};
