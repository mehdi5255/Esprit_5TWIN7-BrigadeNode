/*
 * Validation des formulaires en temps réel (côté navigateur).
 *
 * - Désactive les bulles natives du navigateur (novalidate) et affiche les messages
 *   en français sous chaque champ, pendant la saisie.
 * - Se base sur les attributs HTML déjà présents : required, type="email", min, max,
 *   minlength, maxlength, pattern, accept, data-max-ko (taille maximale d'un fichier).
 * - Mots de passe : liste des règles cochée en direct et vérification de la confirmation.
 *
 * La validation Laravel côté serveur reste la référence : ce script améliore seulement
 * le confort de saisie.
 */
(function () {
    'use strict';

    const CLASSES_ERREUR = ['border-red-400', 'ring-4', 'ring-red-100'];
    const CLASSES_VALIDE = ['border-emerald-400'];
    const CLASSES_NEUTRE = ['border-slate-200', 'border-slate-300'];

    const formaterDate = (iso) => iso.split('-').reverse().join('/');

    /** Conteneur où afficher le message : le fieldset d'un groupe de boutons radio, sinon le <label> du champ. */
    function conteneur(champ) {
        if (champ.type === 'radio') {
            return champ.closest('fieldset') || champ.parentElement;
        }
        return champ.closest('label') || champ.parentElement;
    }

    /** Élément du message d'erreur (créé une seule fois par conteneur). */
    function zoneMessage(champ) {
        const parent = conteneur(champ);
        let zone = parent.querySelector(':scope > [data-erreur-js]');
        if (!zone) {
            zone = document.createElement('span');
            zone.dataset.erreurJs = '';
            zone.className = 'mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600';
            zone.setAttribute('role', 'alert');
            zone.hidden = true;
            parent.appendChild(zone);
        }
        return zone;
    }

    /** Masque le message renvoyé par le serveur dès que l'utilisateur corrige le champ. */
    function masquerErreurServeur(champ) {
        conteneur(champ).querySelectorAll(':scope > .text-red-600:not([data-erreur-js])').forEach((el) => { el.hidden = true; });
    }

    /** Renvoie le message d'erreur du champ, ou '' s'il est valide. */
    function verifier(champ) {
        const valeur = champ.type === 'file' ? '' : champ.value.trim();

        // Groupe de boutons radio : au moins un choix coché
        if (champ.type === 'radio') {
            const groupe = champ.form.querySelectorAll(`input[type="radio"][name="${CSS.escape(champ.name)}"]`);
            const requis = [...groupe].some((r) => r.required);
            return requis && ![...groupe].some((r) => r.checked) ? 'Veuillez faire un choix.' : '';
        }

        if (champ.type === 'checkbox') {
            return champ.required && !champ.checked ? 'Cette case est obligatoire.' : '';
        }

        if (champ.type === 'file') {
            const fichier = champ.files[0];
            if (!fichier) {
                return champ.required ? 'Veuillez choisir un fichier.' : '';
            }
            if ((champ.accept || '').includes('image') && !fichier.type.startsWith('image/')) {
                return 'Le fichier doit être une image (PNG, JPG…).';
            }
            const maxKo = Number(champ.dataset.maxKo || 0);
            if (maxKo && fichier.size > maxKo * 1024) {
                return `Fichier trop volumineux : ${(maxKo / 1024).toLocaleString('fr-FR')} Mo maximum.`;
            }
            return '';
        }

        if (valeur === '') {
            return champ.required ? 'Ce champ est obligatoire.' : '';
        }

        if (champ.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(valeur)) {
            return 'Adresse e-mail invalide (ex. nom@exemple.fr).';
        }

        if (champ.type === 'number') {
            const nombre = Number(valeur.replace(',', '.'));
            if (Number.isNaN(nombre)) return 'Veuillez saisir un nombre.';
            if (champ.min !== '' && nombre < Number(champ.min)) return `La valeur doit être supérieure ou égale à ${champ.min}.`;
            if (champ.max !== '' && nombre > Number(champ.max)) return `La valeur doit être inférieure ou égale à ${champ.max}.`;
            if (champ.step === '1' && !Number.isInteger(nombre)) return 'Veuillez saisir un nombre entier.';
        }

        if (champ.type === 'date') {
            if (champ.min && valeur < champ.min) return `La date doit être au plus tôt le ${formaterDate(champ.min)}.`;
            if (champ.max && valeur > champ.max) return `La date doit être au plus tard le ${formaterDate(champ.max)}.`;
        }

        if (champ.minLength > 0 && valeur.length < champ.minLength) return `${champ.minLength} caractères minimum (${valeur.length} actuellement).`;
        if (champ.maxLength > 0 && valeur.length > champ.maxLength) return `${champ.maxLength} caractères maximum.`;
        if (champ.pattern && !new RegExp(`^(?:${champ.pattern})$`).test(valeur)) return champ.title || 'Format invalide.';

        return verifierMotDePasse(champ, valeur);
    }

    // ── Mots de passe ─────────────────────────────────────────────────

    const MIN_MOT_DE_PASSE = 8;
    const estNouveauMotDePasse = (champ) => champ.type === 'password' && champ.autocomplete === 'new-password' && champ.name !== 'password_confirmation';

    function verifierMotDePasse(champ, valeur) {
        const form = champ.form;

        if (estNouveauMotDePasse(champ)) {
            if (valeur.length < MIN_MOT_DE_PASSE) return `Le mot de passe doit contenir au moins ${MIN_MOT_DE_PASSE} caractères.`;
            const actuel = form.querySelector('input[name="current_password"]');
            if (actuel && actuel.value && actuel.value === valeur) return 'Le nouveau mot de passe doit être différent de l’actuel.';
        }

        if (champ.name === 'password_confirmation') {
            const motDePasse = form.querySelector('input[name="password"]');
            if (motDePasse && valeur !== motDePasse.value) return 'Les mots de passe ne correspondent pas.';
        }

        return '';
    }

    /** Règles et force du mot de passe, mises à jour à chaque frappe. */
    function installerAideMotDePasse(champ) {
        const aide = document.createElement('div');
        aide.className = 'mt-2 space-y-1.5 text-xs';
        aide.innerHTML = `
            <div class="h-1.5 overflow-hidden rounded-full bg-slate-100"><div data-jauge class="h-full w-0 rounded-full transition-all"></div></div>
            <p data-force class="font-semibold text-slate-400">Force du mot de passe</p>
            <ul class="space-y-0.5">
                <li data-regle="longueur">${MIN_MOT_DE_PASSE} caractères minimum (obligatoire)</li>
                <li data-regle="casse">Majuscules et minuscules (conseillé)</li>
                <li data-regle="chiffre">Au moins un chiffre (conseillé)</li>
                <li data-regle="symbole">Au moins un symbole, ex. ! ? @ (conseillé)</li>
            </ul>`;
        conteneur(champ).appendChild(aide);

        // L'aide remplace la note « 8 caractères minimum » du composant champ.
        conteneur(champ).querySelectorAll(':scope > .text-slate-400').forEach((el) => { el.hidden = true; });

        const maj = () => {
            const v = champ.value;
            const regles = {
                longueur: v.length >= MIN_MOT_DE_PASSE,
                casse: /[a-z]/.test(v) && /[A-Z]/.test(v),
                chiffre: /\d/.test(v),
                symbole: /[^A-Za-z0-9]/.test(v),
            };
            Object.entries(regles).forEach(([regle, ok]) => {
                const li = aide.querySelector(`[data-regle="${regle}"]`);
                li.className = ok ? 'text-emerald-600' : 'text-slate-400';
                li.textContent = (ok ? '✓ ' : '○ ') + li.textContent.replace(/^[✓○] /, '');
            });

            const score = v.length === 0 ? 0 : Object.values(regles).filter(Boolean).length + (v.length >= 12 ? 1 : 0);
            const niveaux = [
                [0, '0%', 'bg-slate-200', 'text-slate-400', 'Force du mot de passe'],
                [1, '25%', 'bg-red-500', 'text-red-600', 'Très faible'],
                [2, '45%', 'bg-orange-500', 'text-orange-600', 'Faible'],
                [3, '65%', 'bg-amber-500', 'text-amber-600', 'Moyen'],
                [4, '85%', 'bg-emerald-500', 'text-emerald-600', 'Fort'],
                [5, '100%', 'bg-emerald-600', 'text-emerald-700', 'Très fort'],
            ];
            const [, largeur, couleur, texte, libelle] = niveaux[Math.min(score, 5)];
            const jauge = aide.querySelector('[data-jauge]');
            jauge.style.width = regles.longueur || v.length === 0 ? largeur : '15%';
            jauge.className = `h-full rounded-full transition-all ${regles.longueur || v.length === 0 ? couleur : 'bg-red-500'}`;
            const force = aide.querySelector('[data-force]');
            force.className = `font-semibold ${regles.longueur || v.length === 0 ? texte : 'text-red-600'}`;
            force.textContent = !regles.longueur && v.length > 0 ? `Trop court (${v.length}/${MIN_MOT_DE_PASSE})` : libelle;
        };

        champ.addEventListener('input', maj);
        maj();
    }

    /** Message positif sous la confirmation quand les deux mots de passe correspondent. */
    function afficherCorrespondance(confirmation) {
        const zone = zoneMessage(confirmation);
        if (confirmation.value && !verifier(confirmation)) {
            zone.hidden = false;
            zone.className = 'mt-1.5 flex items-center gap-1 text-xs font-medium text-emerald-600';
            zone.textContent = '✓ Les mots de passe correspondent.';
        }
    }

    // ── Affichage ─────────────────────────────────────────────────────

    /** Un champ sans aucune contrainte (recherche, filtre…) n'est ni vérifié ni coloré. */
    function aDesRegles(champ) {
        return champ.required || champ.pattern || champ.minLength > 0 || champ.maxLength > 0 || champ.dataset.maxKo
            || ['email', 'number', 'date', 'password', 'file'].includes(champ.type) || champ.min !== '' || champ.max !== '';
    }

    function afficher(champ, forcer = false) {
        if (!aDesRegles(champ)) return true;
        const message = verifier(champ);
        const zone = zoneMessage(champ);
        // Un champ vide n'est signalé qu'après avoir été quitté ou à l'envoi du formulaire.
        const vide = champ.type !== 'radio' && champ.type !== 'file' && champ.value.trim() === '';
        const montrer = message && (forcer || champ.dataset.touche === '1' || !vide);

        zone.hidden = !montrer;
        zone.className = 'mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600';
        zone.textContent = montrer ? message : '';

        if (champ.type !== 'radio' && champ.type !== 'checkbox') {
            champ.classList.remove(...CLASSES_ERREUR, ...CLASSES_VALIDE);
            if (montrer) {
                champ.classList.remove(...CLASSES_NEUTRE);
                champ.classList.add(...CLASSES_ERREUR);
            } else if (!message && !vide && champ.type !== 'file') {
                champ.classList.remove(...CLASSES_NEUTRE);
                champ.classList.add(...CLASSES_VALIDE);
            } else {
                champ.classList.add(champ.type === 'file' ? 'border-slate-300' : 'border-slate-200');
            }
        }
        champ.setAttribute('aria-invalid', montrer ? 'true' : 'false');

        if (champ.name === 'password_confirmation') afficherCorrespondance(champ);
        return !message;
    }

    function champsDe(form) {
        return [...form.elements].filter((el) =>
            ['INPUT', 'SELECT', 'TEXTAREA'].includes(el.tagName)
            && !['hidden', 'submit', 'button', 'reset'].includes(el.type)
            && !el.disabled
            && !el.closest('[hidden]'));
    }

    function installer(form) {
        form.noValidate = true; // plus de bulles natives du navigateur

        const champs = champsDe(form);
        if (champs.length === 0) return;

        champs.forEach((champ) => {
            const evenement = ['radio', 'checkbox', 'file', 'select-one', 'date'].includes(champ.type) ? 'change' : 'input';

            champ.addEventListener(evenement, () => {
                masquerErreurServeur(champ);
                afficher(champ);
                // Le mot de passe modifié : la confirmation est revérifiée aussitôt.
                if (champ.name === 'password' || champ.name === 'current_password') {
                    const confirmation = form.querySelector('input[name="password_confirmation"]');
                    const nouveau = form.querySelector('input[name="password"]');
                    if (confirmation && confirmation.value) afficher(confirmation);
                    if (champ.name === 'current_password' && nouveau && nouveau.value) afficher(nouveau);
                }
            });
            champ.addEventListener('blur', () => { champ.dataset.touche = '1'; afficher(champ); });

            if (estNouveauMotDePasse(champ)) installerAideMotDePasse(champ);
        });

        form.addEventListener('submit', (evt) => {
            const invalides = champsDe(form).filter((champ) => !afficher(champ, true));
            if (invalides.length > 0) {
                evt.preventDefault();
                evt.stopImmediatePropagation(); // pas de fenêtre de confirmation si le formulaire est invalide
                invalides[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                invalides[0].focus({ preventScroll: true });
            }
        }, true);
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('form').forEach(installer);
    });
})();
