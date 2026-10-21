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
        // Collectes et distributions organisées par une association.
        Schema::create('evenements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('association_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('titre');
            $table->text('description')->nullable();
            $table->string('lieu');
            $table->dateTime('debut');
            $table->dateTime('fin');
            $table->unsignedSmallInteger('places')->nullable();
            $table->boolean('annule')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evenements');
    }
};
