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
        $svg = (new Writer(new ImageRenderer(
            new RendererStyle($taille, 1, null, null, Fill::uniformColor(new Rgb(255, 255, 255), new Rgb(15, 23, 42))),
            new SvgImageBackEnd
        )))->writeString($texte, 'UTF-8');

        return trim(substr($svg, strpos($svg, "\n") + 1));
    }
}
