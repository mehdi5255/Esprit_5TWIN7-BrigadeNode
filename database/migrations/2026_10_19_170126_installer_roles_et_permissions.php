<?php

use App\Support\RolesPermissions;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Rôles et permissions de TextileCycle (Spatie), et rattachement des comptes existants à leur rôle.
     * Aucune donnée n'est supprimée.
     */
    public function up(): void
    {
        RolesPermissions::installer();
    }

    public function down(): void
    {
        // Les tables sont supprimées par la migration create_permission_tables.
    }
};
