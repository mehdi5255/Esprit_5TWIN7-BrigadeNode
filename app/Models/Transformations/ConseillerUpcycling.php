<?php

namespace App\Models\Transformations;

use App\Models\Vetements\Vetement;
use App\Support\Cloudflare;
use App\Support\Gemini;
use App\Support\Photo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Assistant d'upcycling (IA Gemini) pour le particulier :
 *  - idees()  : « Que faire de ce vêtement ? » — Gemini regarde la photo et la fiche du vêtement,
 *               propose 3 projets et les relie aux inspirations de la galerie de la même catégorie ;
 *  - apercu() : image du vêtement une fois transformé selon l'idée choisie — retouche de la vraie photo
 *               par Gemini (payant), sinon illustration dessinée par Cloudflare Workers AI (gratuit)
 *               d'après la description du vêtement rédigée par Gemini.
 * Sans IA disponible, idees() propose les inspirations de la galerie et apercu() la photo
 * de l'inspiration la plus proche : la page fonctionne toujours.
 */
class ConseillerUpcycling
{
    public const NB_IDEES = 3;

    /** Dossier des aperçus générés ; chaque fichier est préfixé par l'id du particulier. */
    public const DOSSIER_APERCUS = 'transformations/apercus';

    public const DIFFICULTES = ['facile', 'moyenne', 'avancée'];

    /**
     * @return array{base: string, idees: array<int, array>}
     */
    public function idees(Vetement $vetement): array
    {
        $vetement->loadMissing(['categorie', 'matieres']);
        $inspirations = $this->inspirationsCompatibles($vetement);

        // Même vêtement, même fiche : on réutilise la réponse de l'IA (quota gratuit limité).
        $cle = "upcycling.idees.{$vetement->id}.{$vetement->updated_at?->timestamp}";
        $ia = Cache::get($cle) ?? $this->demanderIdees($vetement, $inspirations);
        if ($ia === null) {
            return ['base' => 'inspirations', 'idees' => $this->ideesDepuisGalerie($inspirations)];
        }
        Cache::put($cle, $ia, now()->addDay());

        return ['base' => 'gemini', 'idees' => collect($ia)->take(self::NB_IDEES)->map(fn ($idee) => [
            'titre' => Str::limit(trim((string) $idee['titre']), 80),
            'description' => Str::limit(trim((string) $idee['description']), 400),
            'difficulte' => in_array($idee['difficulte'] ?? null, self::DIFFICULTES, true) ? $idee['difficulte'] : 'moyenne',
            'temps' => Str::limit(trim((string) ($idee['temps_estime'] ?? '')), 40),
            'matieres' => Str::limit(trim((string) ($idee['matiere_reutilisee'] ?? '')), 120),
            // Seuls les identifiants réellement proposés à l'IA sont retenus.
            'inspirations' => $inspirations->whereIn('id', $idee['inspirations'] ?? [])->take(2)->map(fn ($i) => $this->resumeInspiration($i))->values()->all(),
        ])->values()->all()];
    }

    /**
     * Aperçu du vêtement transformé.
     *
     * @return array{base: string, url: ?string, chemin: ?string, inspiration: ?array}
     */
    public function apercu(Vetement $vetement, string $idee): array
    {
        $vetement->loadMissing(['categorie', 'matieres']);
        $photo = Gemini::configure() ? Photo::contenu($vetement->photo) : null;

        // 1. Gemini image (payant) : modifie la vraie photo du vêtement.
        $base = 'gemini';
        $image = $photo ? Gemini::image(
            "Photo produit réaliste du vêtement de l'image une fois transformé par un atelier d'upcycling, selon ce projet : {$idee}. "
            .'Garde exactement le même tissu, la même couleur et les mêmes motifs que le vêtement d\'origine. '
            .'Fond clair et neutre, éclairage doux, vêtement entier visible, sans texte ni personne.',
            [$photo],
        ) : null;

        // 2. Cloudflare (gratuit) : illustration dessinée d'après la description du vêtement.
        if (! $image && Cloudflare::configure()) {
            $base = 'illustration';
            $image = Cloudflare::image($this->descriptionIllustration($vetement, $idee, $photo));
        }

        if ($image) {
            $extension = ['image/jpeg' => 'jpg', 'image/webp' => 'webp'][$image['mime']] ?? 'png';
            $chemin = self::DOSSIER_APERCUS.'/'.$vetement->user_id.'-'.Str::uuid().'.'.$extension;
            Storage::disk('public')->put($chemin, $image['data']);

            return ['base' => $base, 'url' => Storage::disk('public')->url($chemin), 'chemin' => $chemin, 'inspiration' => null];
        }

        // Secours : l'inspiration de la galerie dont le texte ressemble le plus à l'idée.
        $proche = $this->inspirationsCompatibles($vetement)->filter(fn ($i) => $i->photo)
            ->sortByDesc(fn ($i) => similar_text(Str::lower($idee), Str::lower($i->titre.' '.$i->description)))
            ->first();

        return ['base' => $proche ? 'inspiration' : 'aucune', 'url' => $proche?->photoUrl(), 'chemin' => null, 'inspiration' => $proche ? $this->resumeInspiration($proche) : null];
    }

    /**
     * Consigne (en anglais, mieux comprise par le modèle d'image) décrivant le résultat :
     * Gemini la rédige en regardant la photo ; sans lui, on la compose à partir de la fiche.
     */
    private function descriptionIllustration(Vetement $vetement, string $idee, ?array $photo): string
    {
        $style = 'Professional product photo, the whole garment visible, flat lay on a plain light background, soft light, no person, no text, no logo.';

        $ia = Gemini::json(
            'You write prompts for a text-to-image model. Describe in English, in at most 60 words, the garment that results from the upcycling project, '
            .'made from the original garment described below and shown in the photo: keep its exact color, fabric, texture and patterns. Describe only the final object.',
            "Original garment: {$vetement->nom} ({$vetement->categorie?->libelle}), color: ".($vetement->couleur ?: 'see photo')
            .', fabric: '.($vetement->matieres->pluck('nom')->join(', ') ?: ($vetement->matiere ?: 'see photo'))
            ."\nUpcycling project (in French): {$idee}",
            ['type' => 'OBJECT', 'properties' => ['prompt' => ['type' => 'STRING']], 'required' => ['prompt']],
            array_filter([$photo]),
        );

        $description = trim((string) ($ia['prompt'] ?? ''))
            ?: "{$idee}. Made from a ".trim(($vetement->couleur ?? '').' '.($vetement->matieres->pluck('nom')->join(' and ') ?: $vetement->matiere ?? '').' '.$vetement->nom).'.';

        return $description.' '.$style;
    }

    /** Un aperçu ne peut être joint à une demande que par le particulier qui l'a généré. */
    public static function apercuAppartientA(?string $chemin, int $userId): bool
    {
        return $chemin !== null
            && preg_match('#^'.preg_quote(self::DOSSIER_APERCUS, '#').'/'.$userId.'-[0-9a-f-]{36}\.(png|jpe?g|webp)$#', $chemin) === 1
            && Storage::disk('public')->exists($chemin);
    }

    private function demanderIdees(Vetement $vetement, Collection $inspirations): ?array
    {
        if (! Gemini::configure()) {
            return null;
        }

        $fiche = implode("\n", array_filter([
            'Vêtement : '.$vetement->nom.' ('.$vetement->categorie?->libelle.')',
            $vetement->matieres->isNotEmpty()
                ? 'Composition : '.$vetement->matieres->map(fn ($m) => $m->nom.' '.$m->pivot->pourcentage.' %')->join(', ')
                : ($vetement->matiere ? 'Matière : '.$vetement->matiere : null),
            $vetement->couleur ? 'Couleur : '.$vetement->couleur : null,
            $vetement->taille ? 'Taille : '.$vetement->taille : null,
            'État : '.$vetement->etat->label(),
            $vetement->description ? 'Description du propriétaire : '.$vetement->description : null,
        ]));
        $galerie = $inspirations->map(fn ($i) => "- [{$i->id}] {$i->titre} : ".Str::limit($i->description, 120))->join("\n");

        $reponse = Gemini::json(
            'Tu es un styliste spécialisé en upcycling qui conseille des particuliers français. '
            .'À partir de la photo et de la fiche du vêtement, propose exactement '.self::NB_IDEES.' projets de transformation réalisables par un atelier de couture, '
            .'variés (un simple, un plus créatif, un utile au quotidien), qui tiennent compte de la matière et de l\'état. '
            .'Pour chaque projet, cite les identifiants des modèles de la galerie qui s\'en rapprochent vraiment (liste vide sinon). '
            .'Réponds en français, descriptions de 2 phrases maximum. Difficulté : facile, moyenne ou avancée.',
            $fiche."\n\nModèles de la galerie (identifiant entre crochets) :\n".($galerie ?: '- aucun'),
            [
                'type' => 'OBJECT',
                'properties' => ['idees' => ['type' => 'ARRAY', 'items' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'titre' => ['type' => 'STRING'],
                        'description' => ['type' => 'STRING'],
                        'difficulte' => ['type' => 'STRING', 'enum' => self::DIFFICULTES],
                        'temps_estime' => ['type' => 'STRING'],
                        'matiere_reutilisee' => ['type' => 'STRING'],
                        'inspirations' => ['type' => 'ARRAY', 'items' => ['type' => 'INTEGER']],
                    ],
                    'required' => ['titre', 'description', 'difficulte', 'temps_estime', 'matiere_reutilisee', 'inspirations'],
                ]]],
                'required' => ['idees'],
            ],
            array_filter([Photo::contenu($vetement->photo)]),
        );

        $idees = array_values(array_filter($reponse['idees'] ?? [], fn ($i) => filled($i['titre'] ?? null) && filled($i['description'] ?? null)));

        return $idees ?: null;
    }

    /** Inspirations visibles de la catégorie du vêtement : les seules qu'on peut demander pour lui. */
    private function inspirationsCompatibles(Vetement $vetement): Collection
    {
        return Inspiration::visibles()->with('atelier')->where('categorie_id', $vetement->categorie_id)->latest()->get();
    }

    private function ideesDepuisGalerie(Collection $inspirations): array
    {
        return $inspirations->take(self::NB_IDEES)->map(fn ($i) => [
            'titre' => $i->titre,
            'description' => Str::limit($i->description, 200),
            'difficulte' => null,
            'temps' => $i->delai_jours ? $i->delai_jours.' jours en atelier' : '',
            'matieres' => '',
            'inspirations' => [$this->resumeInspiration($i)],
        ])->values()->all();
    }

    private function resumeInspiration(Inspiration $inspiration): array
    {
        return [
            'id' => $inspiration->id,
            'titre' => $inspiration->titre,
            'atelier' => $inspiration->atelier->nom,
            'prix' => $inspiration->prixFormate(),
            'photo' => $inspiration->photoUrl(),
        ];
    }
}
