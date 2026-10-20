<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/** Comparaison simple de textes en français : mots significatifs et ressemblance (indice de Jaccard). */
class Texte
{
    private const MOTS_VIDES = ['les', 'des', 'une', 'sur', 'avec', 'pour', 'dans', 'sans', 'est', 'pas', 'par', 'aux', 'tres', 'peu', 'plus', 'tout',
        'bien', 'fait', 'etre', 'qui', 'que', 'son', 'ses', 'mon', 'mes', 'suis', 'veux', 'voudrais', 'faire', 'cherche', 'besoin', 'mais', 'aussi', 'chez'];

    /** « Fermetures éclair cassées » → [fermeture, eclair, cassee] */
    public static function mots(string $texte): Collection
    {
        return collect(preg_split('/[^a-z0-9]+/', Str::lower(Str::ascii($texte))))
            ->filter(fn ($mot) => strlen($mot) >= 3 && ! in_array($mot, self::MOTS_VIDES, true) && ! is_numeric($mot))
            ->map(fn ($mot) => rtrim($mot, 'sx'))
            ->unique()->values();
    }

    /** Mots communs / mots distincts des deux textes (0 à 1). */
    public static function ressemblance(Collection $a, Collection $b): float
    {
        $union = $a->merge($b)->unique()->count();

        return $union === 0 ? 0.0 : round($a->intersect($b)->count() / $union, 3);
    }

    /** Part des mots de la recherche retrouvés dans le texte (0 à 1) : utile quand le texte est long. */
    public static function couverture(Collection $recherche, Collection $texte): float
    {
        return $recherche->isEmpty() ? 0.0 : round($recherche->intersect($texte)->count() / $recherche->count(), 3);
    }
}
