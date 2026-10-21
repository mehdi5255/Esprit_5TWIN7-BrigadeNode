<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Position GPS du lieu de l'événement, choisie par l'association sur la carte, affichée au particulier.
     */
    public function up(): void
    {
        Schema::table('evenements', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('lieu');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('evenements', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
