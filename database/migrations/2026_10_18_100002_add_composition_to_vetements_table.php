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
        Schema::table('vetements', function (Blueprint $table) {
            $table->unsignedSmallInteger('poids_grammes')->nullable()->after('matiere');
            // Vrai si l'étiquette est perdue : composition typique de la catégorie.
            $table->boolean('composition_estimee')->default(false)->after('poids_grammes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vetements', function (Blueprint $table) {
            $table->dropColumn(['poids_grammes', 'composition_estimee']);
        });
    }
};
