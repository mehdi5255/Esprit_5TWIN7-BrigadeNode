/*
 * Fenêtre de confirmation aux couleurs du site, à la place du confirm() du navigateur.
 *
 * Usage dans une vue :
 *     <form method="POST" action="…" data-confirmer="Supprimer ce service ?">
 *     <form … data-confirmer="Annuler cette demande ?" data-confirmer-bouton="Oui, annuler">
 *
 * Le bouton de confirmation reprend le premier mot du message (Supprimer, Bloquer…),
 * sauf si data-confirmer-bouton est fourni. Les actions destructrices s'affichent en rouge,
 * les annulations en orange. Échap ou un clic à côté referment la fenêtre.
 *
 * La validation (validation.js) passe avant : un formulaire incomplet affiche ses erreurs
 * au lieu de la fenêtre.
 */
(function () {
    'use strict';

    const DESTRUCTEURS = ['supprimer', 'bloquer', 'désactiver'];

    const ICONES = {
        danger: '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M8 6V4h8v2m-9 0 1 14h8l1-14"/></svg>',
        attention: '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>',
    };

    let fenetre = null;

    /** Construit la fenêtre une seule fois, cachée dans la page. */
    function creerFenetre() {
        const racine = document.createElement('div');
        racine.hidden = true;
        racine.className = 'fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm';
        racine.innerHTML = `
            <div role="alertdialog" aria-modal="true" aria-labelledby="confirmation-titre" aria-describedby="confirmation-message"
                 class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl">
                <div class="flex items-start gap-4">
                    <span data-icone class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl"></span>
                    <div class="min-w-0 flex-1">
                        <h2 id="confirmation-titre" class="font-display text-lg font-bold text-slate-900">Confirmation</h2>
                        <p id="confirmation-message" data-message class="mt-1 text-sm leading-6 text-slate-600"></p>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" data-annuler class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Retour</button>
                    <button type="button" data-valider class="rounded-xl px-4 py-2.5 text-sm font-semibold text-white shadow-sm"></button>
                </div>
            </div>`;
        document.body.appendChild(racine);

        // Clic à côté de la boîte : on referme.
        racine.addEventListener('click', (evt) => { if (evt.target === racine) fermer(); });
        racine.querySelector('[data-annuler]').addEventListener('click', fermer);
        racine.addEventListener('keydown', (evt) => {
            if (evt.key === 'Escape') fermer();
            // Le focus reste dans la fenêtre (Tab / Maj+Tab entre les deux boutons).
            if (evt.key === 'Tab') {
                const boutons = [...racine.querySelectorAll('button')];
                const i = boutons.indexOf(document.activeElement);
                evt.preventDefault();
                boutons[(i + (evt.shiftKey ? -1 : 1) + boutons.length) % boutons.length].focus();
            }
        });
        return racine;
    }

    let formulaireEnCours = null;
    let focusAvant = null;

    function ouvrir(form, declencheur) {
        fenetre = fenetre || creerFenetre();
        formulaireEnCours = form;
        focusAvant = declencheur || document.activeElement;

        const message = form.dataset.confirmer;
        const premierMot = message.split(/\s+/)[0].toLowerCase();
        const danger = DESTRUCTEURS.includes(premierMot);

        fenetre.querySelector('[data-message]').textContent = message;
        fenetre.querySelector('[data-icone]').className = `grid h-12 w-12 shrink-0 place-items-center rounded-2xl ${danger ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-600'}`;
        fenetre.querySelector('[data-icone]').innerHTML = danger ? ICONES.danger : ICONES.attention;

        const valider = fenetre.querySelector('[data-valider]');
        valider.textContent = form.dataset.confirmerBouton || message.split(/\s+/)[0];
        valider.className = `rounded-xl px-4 py-2.5 text-sm font-semibold text-white shadow-sm ${danger ? 'bg-red-600 hover:bg-red-700' : 'bg-amber-600 hover:bg-amber-700'}`;
        valider.onclick = () => {
            const cible = formulaireEnCours;
            fermer(false);
            cible.dataset.confirme = '1';
            HTMLFormElement.prototype.submit.call(cible); // envoi direct, sans redemander
        };

        fenetre.hidden = false;
        document.body.classList.add('overflow-hidden');
        // Pour une suppression, le focus va sur « Retour » : Entrée ne supprime pas par erreur.
        fenetre.querySelector(danger ? '[data-annuler]' : '[data-valider]').focus();
    }

    function fermer(rendreFocus = true) {
        if (!fenetre) return;
        fenetre.hidden = true;
        document.body.classList.remove('overflow-hidden');
        formulaireEnCours = null;
        if (rendreFocus && focusAvant && typeof focusAvant.focus === 'function') focusAvant.focus();
    }

    // Écouteur en phase de bouillonnement : validation.js (phase de capture) passe avant.
    document.addEventListener('submit', (evt) => {
        const form = evt.target;
        if (!(form instanceof HTMLFormElement) || !form.dataset.confirmer || form.dataset.confirme === '1') return;
        evt.preventDefault();
        ouvrir(form, evt.submitter);
    });
})();
