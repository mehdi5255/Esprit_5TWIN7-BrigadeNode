<?php

namespace App\Notifications;

use App\Models\Reparations\DemandeReparation;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Module 2 — E-mail au particulier quand son vêtement réparé est prêt à être récupéré. */
class ReparationPrete extends Notification
{
    public function __construct(public DemandeReparation $demande) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $demande = $this->demande->loadMissing(['vetement', 'atelier', 'facture']);
        $atelier = $demande->atelier;

        $mail = (new MailMessage)
            ->subject("Votre {$demande->vetement->nom} est prêt ! ({$demande->reference()})")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Bonne nouvelle : {$atelier->nom} a terminé la réparation de votre {$demande->vetement->nom} ({$demande->type_reparation->label()}).")
            ->line('Vous pouvez passer le récupérer dès maintenant.');

        if ($adresse = $atelier->adresseComplete()) {
            $mail->line("**Adresse :** {$adresse}");
        }
        if ($atelier->telephone) {
            $mail->line("**Téléphone :** {$atelier->telephone}");
        }
        if ($facture = $demande->facture) {
            $mail->line('**Montant à régler :** '.number_format((float) $facture->montant_total, 2, ',', ' ').' € (facture '.$facture->numero().')');
        }

        return $mail
            ->action('Voir ma réparation', route('reparations.show', $demande))
            ->line('Merci de faire réparer plutôt que jeter : votre vêtement repart pour une nouvelle vie.')
            ->salutation('L’équipe TextileCycle');
    }
}
