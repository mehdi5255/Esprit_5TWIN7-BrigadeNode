<?php

namespace App\Notifications;

use App\Models\Dons\DemandeDon;
use App\Models\Dons\Evenement;
use Illuminate\Notifications\Notification;

/** Collecte annulée : le particulier doit déposer son don au local de l'association. */
class EvenementAnnule extends Notification
{
    public function __construct(public Evenement $evenement, public DemandeDon $demande)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => DemandeDon::LIBELLE,
            'reference' => $this->demande->reference(),
            'statut' => $this->demande->statut->value,
            'message' => "La collecte « {$this->evenement->titre} » du {$this->evenement->debut->format('d/m/Y')} est annulée. Vous pourrez déposer « {$this->demande->vetement->nom} » au local de {$this->evenement->association->nom}.",
            'url' => route('dons.show', $this->demande, absolute: false),
        ];
    }
}
