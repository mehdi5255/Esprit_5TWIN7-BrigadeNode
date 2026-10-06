<?php

namespace App\Notifications;

use App\Models\Dons\DemandeDon;
use App\Models\Reparations\DemandeReparation;
use App\Models\Transformations\DemandeTransformation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;

/** Changement d'étape d'une demande : au particulier, ou à la structure pour une nouvelle demande. */
class DemandeMiseAJour extends Notification
{
    public function __construct(public Model $demande, public bool $pourStructure = false)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->demande::LIBELLE,
            'reference' => $this->demande->reference(),
            'statut' => $this->demande->statut->value,
            'message' => $this->demande->messageNotification($this->pourStructure),
            'url' => $this->url(),
        ];
    }

    private function url(): string
    {
        $segment = match ($this->demande::class) {
            DemandeReparation::class => 'reparations',
            DemandeTransformation::class => 'transformations',
            DemandeDon::class => 'dons',
        };

        if ($this->pourStructure) {
            $espace = $this->demande instanceof DemandeDon ? 'association' : 'atelier';

            return route("{$espace}.{$segment}.show", $this->demande, absolute: false);
        }

        return route("{$segment}.show", $this->demande, absolute: false);
    }
}
