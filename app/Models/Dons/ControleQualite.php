<?php

namespace App\Models\Dons;

use App\Enums\OrientationDon;
use App\Models\Vetements\Vetement;
use App\Support\Gemini;
use App\Support\Photo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Contrôle qualité d'un don par photo (IA Gemini) :
 *  - verifier() : côté particulier, avant l'envoi — le vêtement est-il donnable ? Sinon, il est orienté
 *                 vers une réparation (module 2) ou une transformation (module 3) ;
 *  - orienter() : côté association, à la réception — redistribution, boutique solidaire ou recyclage,
 *                 avec le besoin de l'association auquel le don répond.
 * L'IA conseille, l'humain décide : rien n'est bloqué ni enregistré sans validation.
 */
class ControleQualite
{
    /** Avis possibles avant l'envoi. */
    public const VERDICTS = [
        'donnable' => 'Donnable',
        'a_reparer' => 'À réparer d’abord',
        'a_transformer' => 'Plutôt à transformer',
        'recyclage' => 'Pour le recyclage',
    ];

    /**
     * Avec un besoin choisi, vérifie aussi que le vêtement y correspond (catégorie, puis titre et description par l'IA).
     *
     * Renvoie null si l'IA est indisponible.
     *
     * @return array{verdict: string, verdict_libelle: string, etat_observe: string, defauts: string[], conseil: string, besoin: ?array}|array{erreur: string}|null
     */
    public function verifier(Vetement $vetement, ?UploadedFile $photo = null, ?BesoinAssociation $besoin = null): ?array
    {
        if (! Gemini::configure()) {
            return null;
        }
        $vetement->loadMissing('categorie');
        $besoin?->loadMissing('categorie');
        $image = $this->image($vetement, $photo);
        if (! $image) {
            return ['erreur' => 'Ajoutez une photo du vêtement pour le faire vérifier.'];
        }

        $ia = Gemini::json(
            'Tu travailles au tri des dons de vêtements d\'une association solidaire française. Regarde la photo et dis si ce vêtement peut être donné tel quel. '
            .'Verdict : donnable (propre, portable, défauts mineurs) ; a_reparer (bon vêtement mais un défaut réparable : trou, couture, fermeture, bouton) ; '
            .'a_transformer (portable difficilement mais tissu intéressant pour un projet d\'upcycling) ; recyclage (trop usé, taché ou abîmé). '
            .'Défauts : liste courte de ce que tu vois (vide si aucun). Conseil : une phrase au donateur, bienveillante. Réponds en français. '
            .'Si la photo ne montre pas de vêtement, mets est_vetement à false. '
            .'Si un besoin de l\'association est indiqué, dis aussi si le vêtement de la photo y correspond vraiment (type de vêtement, public, saison) '
            .'et explique-le en une phrase dans remarque_besoin (ex. un pull ne répond pas à un besoin de chaussures).',
            $this->fiche($vetement)."\n\n".($besoin
                ? "Besoin choisi par le donateur : « {$besoin->titre} »".($besoin->categorie ? " (catégorie {$besoin->categorie->libelle})" : '').($besoin->description ? " : {$besoin->description}" : '')
                : 'Aucun besoin précis (don libre).'),
            [
                'type' => 'OBJECT',
                'properties' => [
                    'est_vetement' => ['type' => 'BOOLEAN'],
                    'verdict' => ['type' => 'STRING', 'enum' => array_keys(self::VERDICTS)],
                    'etat_observe' => ['type' => 'STRING'],
                    'defauts' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                    'conseil' => ['type' => 'STRING'],
                    'correspond_au_besoin' => ['type' => 'BOOLEAN'],
                    'remarque_besoin' => ['type' => 'STRING'],
                ],
                'required' => ['est_vetement', 'verdict', 'etat_observe', 'defauts', 'conseil', 'correspond_au_besoin', 'remarque_besoin'],
            ],
            [$image],
        );

        if ($ia === null) {
            return null;
        }
        if (! ($ia['est_vetement'] ?? false)) {
            return ['erreur' => 'Aucun vêtement n’a été reconnu sur la photo : photographiez-le en entier, bien éclairé.'];
        }
        $verdict = array_key_exists($ia['verdict'] ?? '', self::VERDICTS) ? $ia['verdict'] : 'donnable';

        return [
            'verdict' => $verdict,
            'verdict_libelle' => self::VERDICTS[$verdict],
            'etat_observe' => Str::limit(self::texte($ia['etat_observe']), 300),
            'defauts' => $this->defauts($ia),
            'conseil' => Str::limit(self::texte($ia['conseil']), 300),
            'besoin' => $besoin ? $this->correspondance($vetement, $besoin, $ia) : null,
        ];
    }

    /**
     * Règle métier : un besoin rattaché à une catégorie n'accepte que les vêtements de cette catégorie.
     * Renvoie le message d'erreur, ou null si le vêtement est compatible.
     */
    public static function incompatibilite(Vetement $vetement, BesoinAssociation $besoin): ?string
    {
        if ($besoin->categorie_id === null || $besoin->categorie_id === $vetement->categorie_id) {
            return null;
        }

        return "Le besoin « {$besoin->titre} » attend des articles de la catégorie « {$besoin->categorie?->libelle} » : votre « {$vetement->nom} » "
            .'('.($vetement->categorie?->libelle ?? 'autre catégorie').') n’y correspond pas. Choisissez un autre besoin, ou un don libre.';
    }

    /** Catégorie d'abord (règle stricte), puis avis de l'IA sur le titre et la description du besoin. */
    private function correspondance(Vetement $vetement, BesoinAssociation $besoin, array $ia): array
    {
        $regle = self::incompatibilite($vetement, $besoin);
        $avis = (bool) ($ia['correspond_au_besoin'] ?? true);

        return [
            'titre' => $besoin->titre,
            'correspond' => $regle === null && $avis,
            'remarque' => $regle ?? Str::limit(self::texte($ia['remarque_besoin'] ?? ''), 300),
        ];
    }

    /**
     * @return array{orientation: string, orientation_libelle: string, justification: string, etat_observe: string, defauts: string[], besoin: ?array}|array{erreur: string}|null
     */
    public function orienter(DemandeDon $don, ?UploadedFile $photo = null): ?array
    {
        if (! Gemini::configure()) {
            return null;
        }
        $don->loadMissing(['vetement.categorie', 'association.besoinsActifs']);
        $image = $this->image($don->vetement, $photo);
        if (! $image) {
            return ['erreur' => 'Prenez une photo du vêtement reçu pour l’analyser.'];
        }
        $besoins = $don->association->besoinsActifs;
        $listeBesoins = $besoins->map(fn ($b) => "- [{$b->id}] {$b->titre} (priorité {$b->priorite->label()})".($b->description ? ' : '.Str::limit($b->description, 100) : ''))->join("\n");
        $orientations = collect(OrientationDon::cases())->map(fn ($o) => "{$o->value} ({$o->description()})")->join(' ; ');

        $ia = Gemini::json(
            'Tu aides une association solidaire française à trier les vêtements donnés. Regarde la photo du vêtement reçu et propose son orientation, '
            ."une valeur parmi : {$orientations}. Redistribution seulement s'il est propre et en bon état ; boutique s'il est en bon état mais peu adapté aux besoins ; "
            .'recyclage s\'il est trop usé, taché ou abîmé. Si tu proposes la redistribution, indique l\'identifiant du besoin de l\'association auquel il répond le mieux '
            .'(0 si aucun). Justification : 2 phrases maximum, en français, pour l\'équipe de l\'association. Si la photo ne montre pas de vêtement, mets est_vetement à false.',
            $this->fiche($don->vetement)."\n\nBesoins actuels de l'association :\n".($listeBesoins ?: '- aucun'),
            [
                'type' => 'OBJECT',
                'properties' => [
                    'est_vetement' => ['type' => 'BOOLEAN'],
                    'orientation' => ['type' => 'STRING', 'enum' => array_column(OrientationDon::cases(), 'value')],
                    'justification' => ['type' => 'STRING'],
                    'etat_observe' => ['type' => 'STRING'],
                    'defauts' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                    'besoin_id' => ['type' => 'INTEGER'],
                ],
                'required' => ['est_vetement', 'orientation', 'justification', 'etat_observe', 'defauts', 'besoin_id'],
            ],
            [$image],
        );

        if ($ia === null) {
            return null;
        }
        if (! ($ia['est_vetement'] ?? false)) {
            return ['erreur' => 'Aucun vêtement n’a été reconnu sur la photo.'];
        }
        $orientation = OrientationDon::tryFrom($ia['orientation'] ?? '') ?? OrientationDon::REDISTRIBUTION;
        // Seul un besoin réellement proposé à l'IA est retenu, et seulement pour une redistribution.
        $besoin = $orientation === OrientationDon::REDISTRIBUTION ? $besoins->firstWhere('id', (int) ($ia['besoin_id'] ?? 0)) : null;

        return [
            'orientation' => $orientation->value,
            'orientation_libelle' => $orientation->label(),
            'justification' => Str::limit(self::texte($ia['justification']), 400),
            'etat_observe' => Str::limit(self::texte($ia['etat_observe']), 300),
            'defauts' => $this->defauts($ia),
            'besoin' => $besoin ? ['id' => $besoin->id, 'titre' => $besoin->titre, 'priorite' => $besoin->priorite->label()] : null,
        ];
    }

    /** Photo fraîchement prise, sinon celle du vêtement dans le dressing. */
    private function image(Vetement $vetement, ?UploadedFile $photo): ?array
    {
        return $photo
            ? ['mime' => $photo->getMimeType() ?: 'image/jpeg', 'data' => $photo->get()]
            : Photo::contenu($vetement->photo);
    }

    private function fiche(Vetement $vetement): string
    {
        return implode("\n", array_filter([
            "Vêtement : {$vetement->nom}".($vetement->categorie ? " ({$vetement->categorie->libelle})" : ''),
            $vetement->taille ? "Taille : {$vetement->taille}" : null,
            "État déclaré par le donateur : {$vetement->etat->label()}",
            $vetement->description ? "Description : {$vetement->description}" : null,
        ]));
    }

    private function defauts(array $ia): array
    {
        return collect($ia['defauts'] ?? [])->map(fn ($d) => Str::limit(self::texte($d), 80))->filter()->take(5)->values()->all();
    }

    /** Texte de l'IA nettoyé (il arrive qu'un accent revienne codé, ex. « effiloch\u00e9 »). */
    private static function texte(mixed $valeur): string
    {
        return trim(preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', fn ($m) => mb_chr(hexdec($m[1])), (string) $valeur));
    }
}
