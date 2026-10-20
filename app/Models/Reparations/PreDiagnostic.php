<?php

namespace App\Models\Reparations;

use App\Enums\TypeReparation;
use App\Models\Vetements\Vetement;
use App\Support\Gemini;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Pré-diagnostic par photo (IA Gemini), côté particulier, avant d'envoyer sa demande :
 * Gemini regarde la photo de l'abîmement et propose le type de réparation, une description
 * claire et une gravité ; la fourchette de prix vient des devis passés comparables (EstimateurDevis).
 * Le particulier vérifie et corrige : rien n'est envoyé à l'atelier sans lui.
 */
class PreDiagnostic
{
    public const GRAVITES = ['légère', 'moyenne', 'importante'];

    /**
     * @return array{type_reparation: string, type_libelle: string, description: string, gravite: string, conseil: ?string, fourchette: ?array}|array{erreur: string}|null
     *         null si l'IA est indisponible
     */
    public function analyser(UploadedFile $photo, ?Vetement $vetement = null, ?int $atelierId = null): ?array
    {
        $types = collect(TypeReparation::cases())->map(fn ($t) => "{$t->value} ({$t->label()})")->join(', ');

        $ia = Gemini::json(
            'Tu es couturier dans un atelier de réparation de vêtements en France. On te montre la photo d\'un vêtement abîmé. '
            .'Identifie le problème visible et rédige, à la première personne comme si c\'était le client, une description claire et factuelle '
            .'pour l\'atelier (emplacement, taille approximative, état du tissu autour), en 2 phrases maximum. '
            ."Type de réparation, une valeur parmi : {$types}. Gravité : légère, moyenne ou importante. "
            .'Conseil : une phrase pour le client en attendant la réparation (ex. ne pas laver en machine). '
            .'Si la photo ne montre pas de vêtement ou aucun abîmement visible, mets est_abime à false.',
            $vetement ? "Vêtement concerné : {$vetement->nom}".($vetement->matiere ? ", matière : {$vetement->matiere}" : '').'.' : 'Vêtement non précisé.',
            [
                'type' => 'OBJECT',
                'properties' => [
                    'est_abime' => ['type' => 'BOOLEAN'],
                    'type_reparation' => ['type' => 'STRING', 'enum' => array_column(TypeReparation::cases(), 'value')],
                    'description' => ['type' => 'STRING'],
                    'gravite' => ['type' => 'STRING', 'enum' => self::GRAVITES],
                    'conseil' => ['type' => 'STRING'],
                ],
                'required' => ['est_abime', 'type_reparation', 'description', 'gravite', 'conseil'],
            ],
            [['mime' => $photo->getMimeType() ?: 'image/jpeg', 'data' => $photo->get()]],
        );

        if ($ia === null) {
            return null;
        }
        if (! ($ia['est_abime'] ?? false)) {
            return ['erreur' => 'Aucun abîmement n’a été reconnu sur cette photo : prenez la zone abîmée en gros plan, bien éclairée.'];
        }

        $type = TypeReparation::tryFrom($ia['type_reparation'] ?? '') ?? TypeReparation::AUTRE;
        $description = Str::limit(trim((string) $ia['description']), 600);

        return [
            'type_reparation' => $type->value,
            'type_libelle' => $type->label(),
            'description' => $description,
            'gravite' => in_array($ia['gravite'] ?? null, self::GRAVITES, true) ? $ia['gravite'] : 'moyenne',
            'conseil' => Str::limit(trim((string) ($ia['conseil'] ?? '')), 200) ?: null,
            'fourchette' => (new EstimateurDevis)->fourchette($type, $description, $atelierId),
        ];
    }
}
