<?php

namespace App\Support;

use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/** QR codes générés côté serveur (bibliothèque BaconQrCode, déjà utilisée par la 2FA). */
class QrCode
{
    /** Image SVG (sans l'en-tête XML, prête à être insérée dans une page) d'un QR code contenant ce texte. */
    public static function svg(string $texte, int $taille = 200): string
    {
        $svg = self::fichierSvg($texte, $taille);

        return trim(substr($svg, strpos($svg, "\n") + 1));
    }

    /**
     * Fichier SVG complet (avec l'en-tête XML), pour un téléchargement. L'en-tête vient de la bibliothèque :
     * l'écrire dans une vue casserait la compilation quand PHP accepte les balises courtes « <? ».
     */
    public static function fichierSvg(string $texte, int $taille = 200): string
    {
        return (new Writer(new ImageRenderer(
            new RendererStyle($taille, 1, null, null, Fill::uniformColor(new Rgb(255, 255, 255), new Rgb(15, 23, 42))),
            new SvgImageBackEnd
        )))->writeString($texte, 'UTF-8');
    }
}
