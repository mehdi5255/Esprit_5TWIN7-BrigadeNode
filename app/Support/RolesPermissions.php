<?php

namespace App\Support;

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Catalogue des rôles et permissions (Spatie Permission).
 *
 * Le rôle d'un compte (colonne « role ») est synchronisé avec son rôle Spatie ;
 * l'accès aux pages est vérifié côté serveur par les middlewares « role: » et « permission: ».
 * Les permissions par défaut ci-dessous peuvent ensuite être ajustées par l'admin
 * (page « Rôles et permissions »), sauf celles de l'administrateur, qui les a toutes.
 */
class RolesPermissions
{
    /** permission => [libellé, espace] */
    public const PERMISSIONS = [
        'gerer-utilisateurs' => ['Valider, bloquer et supprimer les comptes', 'Administration'],
        'gerer-structures' => ['Valider les ateliers et les associations', 'Administration'],
        'gerer-catalogue' => ['Gérer les catégories et les matières', 'Administration'],
        'superviser-demandes' => ['Superviser les demandes et les dépôts', 'Administration'],
        'moderer-avis' => ['Modérer les avis', 'Administration'],
        'voir-rapports' => ['Consulter les rapports d’activité', 'Administration'],
        'gerer-roles' => ['Gérer les rôles et les permissions', 'Administration'],

        'gerer-services' => ['Gérer ses services', 'Atelier'],
        'publier-inspirations' => ['Publier des inspirations', 'Atelier'],
        'gerer-besoins' => ['Publier des besoins', 'Association'],
        'gerer-evenements' => ['Organiser des collectes et distributions', 'Association'],
        'repondre-avis' => ['Répondre aux avis reçus', 'Atelier et association'],

        'publier-avis' => ['Publier des avis', 'Particulier'],
    ];

    /** rôle => permissions par défaut */
    public const ROLES = [
        'admin' => ['*'],
        'moderateur' => ['superviser-demandes', 'moderer-avis'],
        'atelier' => ['gerer-services', 'publier-inspirations', 'repondre-avis'],
        'association' => ['gerer-besoins', 'gerer-evenements', 'repondre-avis'],
        'particulier' => ['publier-avis'],
    ];

    /** Rôles qu'un admin peut attribuer à un compte particulier (les structures gardent leur rôle). */
    public const ROLES_ATTRIBUABLES = ['particulier', 'moderateur'];

    /** Crée les rôles et permissions manquants, avec leurs permissions par défaut, et rattache chaque compte à son rôle. */
    public static function installer(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (array_keys(self::PERMISSIONS) as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach (self::ROLES as $nom => $permissions) {
            $role = Role::findOrCreate($nom, 'web');
            // Seul un rôle nouvellement créé reçoit les permissions par défaut ; l'admin a toujours tout.
            if ($nom === 'admin' || $role->wasRecentlyCreated) {
                $role->syncPermissions($permissions === ['*'] ? array_keys(self::PERMISSIONS) : $permissions);
            }
        }

        User::query()->each(fn (User $user) => $user->syncRoles([$user->role]));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
