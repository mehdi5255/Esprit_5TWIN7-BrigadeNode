<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Photo
{
    /** Public URL for a stored photo path (or an absolute URL used by demo data). */
    public static function url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return str_starts_with($path, 'http') ? $path : Storage::disk('public')->url($path);
    }

    /**
     * Contenu binaire d'une photo (stockée, ou URL de démonstration) pour l'envoyer à une IA.
     *
     * @return array{mime: string, data: string}|null
     */
    public static function contenu(?string $path): ?array
    {
        try {
            if (! $path) {
                return null;
            }
            if (str_starts_with($path, 'http')) {
                $reponse = Http::timeout(15)->get($path)->throw();
                $mime = Str::before((string) $reponse->header('Content-Type'), ';') ?: 'image/jpeg';

                return str_starts_with($mime, 'image/') ? ['mime' => $mime, 'data' => $reponse->body()] : null;
            }
            $disque = Storage::disk('public');

            return $disque->exists($path) ? ['mime' => $disque->mimeType($path) ?: 'image/jpeg', 'data' => $disque->get($path)] : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public static function store(?UploadedFile $file, string $dossier): ?string
    {
        return $file?->store($dossier, 'public');
    }

    public static function delete(?string $path): void
    {
        if ($path && ! str_starts_with($path, 'http')) {
            Storage::disk('public')->delete($path);
        }
    }
}
