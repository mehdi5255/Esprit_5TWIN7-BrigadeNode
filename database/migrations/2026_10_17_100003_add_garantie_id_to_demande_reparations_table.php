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
        // Retour sous garantie : la nouvelle demande pointe vers la garantie utilisée (1-N).
        Schema::table('demande_reparations', function (Blueprint $table) {
            $table->foreignId('garantie_id')->nullable()->after('atelier_id')->constrained('garanties')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('demande_reparations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('garantie_id');
        });
    }
};
