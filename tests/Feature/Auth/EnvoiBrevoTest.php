<?php

// Mise en ligne — avec MAIL_MAILER=brevo, les e-mails partent par l'API HTTPS de Brevo (le SMTP est bloqué par Render).

use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoApiTransport;

test('le mode d’envoi brevo utilise l’API de Brevo avec la clé configurée', function () {
    config(['mail.default' => 'brevo', 'services.brevo.key' => 'cle-de-test']);

    $transport = Mail::mailer('brevo')->getSymfonyTransport();

    expect($transport)->toBeInstanceOf(BrevoApiTransport::class)
        ->and((string) $transport)->toBe('brevo+api://api.brevo.com');
});
