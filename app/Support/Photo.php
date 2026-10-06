<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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
