/*
 * Composition d'un vêtement (formulaire « Ajouter / modifier un vêtement »).
 *  - lignes « matière + pourcentage » ajoutées ou retirées, total affiché en direct ;
 *  - « Je n'ai plus l'étiquette » : composition et poids typiques de la catégorie ;
 *  - « Scanner l'étiquette » : lecture de la photo dans le navigateur (OCR Tesseract.js, rien n'est envoyé
 *    au serveur), puis reconnaissance des matières par leurs noms et abréviations (CO, PES, cotton…).
 * La vérification qui compte reste celle du serveur (total = 100 %, matières distinctes).
 */
(function () {
    'use strict';

    const bloc = document.querySelector('[data-composition]');
    if (!bloc) return;

    const catalogue = JSON.parse(bloc.dataset.catalogue);
    const typiques = JSON.parse(bloc.dataset.typiques);
    const lignes = bloc.querySelector('[data-lignes]');
    const modele = bloc.querySelector('[data-modele]');
    const total = bloc.querySelector('[data-total]');
    const estimee = bloc.querySelector('[data-estimee]');
    const message = bloc.querySelector('[data-scan-message]');
    const formulaire = bloc.closest('form');
    const categorie = formulaire.querySelector('[name=categorie_id]');
    const poids = formulaire.querySelector('[name=poids_grammes]');
    let compteur = lignes.children.length;

    const ajouterLigne = (matiereId = '', pourcentage = '') => {
        const ligne = modele.content.firstElementChild.cloneNode(true);
        ligne.innerHTML = ligne.innerHTML.replaceAll('__i__', compteur++);
        ligne.querySelector('select').value = matiereId;
        ligne.querySelector('input').value = pourcentage;
        lignes.appendChild(ligne);
        if (window.lucide) lucide.createIcons();
        return ligne;
    };

    /** Remplace toutes les lignes par une composition { matiere_id: pourcentage }. */
    const remplir = composition => {
        lignes.innerHTML = '';
        Object.entries(composition).forEach(([id, pct]) => ajouterLigne(id, pct));
        if (!lignes.children.length) ajouterLigne();
        majTotal();
    };

    const majTotal = () => {
        const somme = [...lignes.querySelectorAll('input[type=number]')].reduce((s, champ) => s + (parseInt(champ.value, 10) || 0), 0);
        const remplie = [...lignes.querySelectorAll('select')].some(s => s.value);
        total.textContent = !remplie ? '' : somme === 100 ? 'Total : 100 % ✓' : `Total : ${somme} % (il faut 100 %)`;
        total.className = 'text-sm font-semibold ' + (somme === 100 ? 'text-forest-700' : 'text-amber-600');
    };

    const afficher = (texte, type) => {
        message.textContent = texte;
        message.className = 'rounded-xl px-3 py-2 text-xs ' + ({ ok: 'bg-forest-50 text-forest-800', alerte: 'bg-amber-50 text-amber-800', info: 'bg-slate-50 text-slate-600' })[type];
    };

    const appliquerTypique = () => {
        const typique = typiques[categorie.value];
        if (!typique) return afficher('Choisissez d’abord la catégorie du vêtement.', 'alerte');
        remplir(typique.composition);
        if (!poids.value) poids.value = typique.poids;
        afficher('Composition typique de la catégorie proposée : corrigez-la si vous en savez plus.', 'info');
    };

    bloc.querySelector('[data-ajouter]').addEventListener('click', () => { ajouterLigne(); majTotal(); });
    lignes.addEventListener('click', e => {
        if (!e.target.closest('[data-retirer]')) return;
        e.target.closest('[data-ligne]').remove();
        if (!lignes.children.length) ajouterLigne();
        majTotal();
    });
    lignes.addEventListener('input', majTotal);
    lignes.addEventListener('change', majTotal);
    estimee.addEventListener('change', () => estimee.checked && appliquerTypique());
    categorie.addEventListener('change', () => estimee.checked && appliquerTypique());

    // ── Scan de l'étiquette ──────────────────────────────────────────────
    const normaliser = texte => texte.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

    /** Trouve la matière désignée par un mot de l'étiquette (nom, abréviation ou autre langue). */
    const reconnaitre = mot => {
        mot = normaliser(mot).trim();
        if (mot.length < 2) return null;
        return catalogue.find(m => m.mots.includes(mot))
            || catalogue.find(m => m.mots.some(cle => cle.length >= 4 && (mot.startsWith(cle) || cle.startsWith(mot) && mot.length >= 4)));
    };

    /**
     * « 80% CO 20% PES », « 80 % coton / cotton » (pourcentage avant) ou « Coton 80 % » (après)
     * → { matiere_id: pourcentage }. Les deux lectures sont tentées ; on garde celle qui fait 100 %.
     */
    const analyser = texte => {
        // L'OCR lit parfois « 8o% » pour « 80% ».
        const propre = normaliser(texte).replace(/[|/\\,;]/g, ' ').replace(/(\d)o\s*%/g, '$10%');
        const lire = (motif, avant) => {
            const composition = {};
            for (const r of propre.matchAll(motif)) {
                const [pct, mots] = avant ? [r[1], r[2]] : [r[2], r[1]];
                const matiere = reconnaitre(mots) || reconnaitre(mots.split(/\s+/)[0]);
                const valeur = parseInt(pct, 10);
                if (matiere && valeur >= 1 && valeur <= 100 && !(matiere.id in composition)) composition[matiere.id] = valeur;
            }
            return composition;
        };
        const somme = c => Object.values(c).reduce((s, p) => s + p, 0);
        const avant = lire(/(\d{1,3})\s*%\s*([a-z]+(?:\s+[a-z]+)?)/g, true);
        const apres = lire(/([a-z]+)\s*[:\-]?\s*(\d{1,3})\s*%/g, false);
        if (somme(avant) === 100) return avant;
        if (somme(apres) === 100) return apres;
        return Object.keys(avant).length >= Object.keys(apres).length ? avant : apres;
    };

    const chargerTesseract = () => window.Tesseract ? Promise.resolve() : new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js';
        script.onload = resolve;
        script.onerror = () => reject(new Error('Impossible de charger le lecteur d’étiquettes (connexion internet requise).'));
        document.head.appendChild(script);
    });

    /**
     * Prépare la photo pour l'OCR : agrandie (le texte d'une étiquette fait souvent quelques pixels),
     * en niveaux de gris, contrastée, et tournée de l'angle demandé (photo prise de travers).
     */
    const preparer = (image, angle) => {
        const echelle = Math.min(4, Math.max(1, 1600 / Math.max(image.width, image.height)));
        const w = image.width * echelle, h = image.height * echelle, rad = angle * Math.PI / 180;
        const toile = document.createElement('canvas');
        toile.width = Math.ceil(Math.abs(w * Math.cos(rad)) + Math.abs(h * Math.sin(rad)));
        toile.height = Math.ceil(Math.abs(w * Math.sin(rad)) + Math.abs(h * Math.cos(rad)));
        const ctx = toile.getContext('2d');
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, toile.width, toile.height);
        ctx.translate(toile.width / 2, toile.height / 2);
        ctx.rotate(rad);
        ctx.imageSmoothingQuality = 'high';
        ctx.drawImage(image, -w / 2, -h / 2, w, h);
        const pixels = ctx.getImageData(0, 0, toile.width, toile.height);
        const p = pixels.data;
        for (let i = 0; i < p.length; i += 4) {
            const gris = 0.299 * p[i] + 0.587 * p[i + 1] + 0.114 * p[i + 2];
            p[i] = p[i + 1] = p[i + 2] = Math.max(0, Math.min(255, (gris - 128) * 1.6 + 128));
        }
        ctx.putImageData(pixels, 0, 0);
        return toile;
    };

    /** Lit la photo sous plusieurs angles ; s'arrête dès qu'une lecture donne une composition à 100 %. */
    const lireEtiquette = async photo => {
        const image = await createImageBitmap(photo);
        const lecteur = await Tesseract.createWorker('fra+eng');
        let meilleure = {};
        try {
            for (const angle of [0, -10, 10, -18, 18]) {
                const { data } = await lecteur.recognize(preparer(image, angle));
                const composition = analyser(data.text);
                const somme = Object.values(composition).reduce((s, p) => s + p, 0);
                if (somme === 100) return composition;
                if (Object.keys(composition).length > Object.keys(meilleure).length) meilleure = composition;
            }
            return meilleure;
        } finally {
            await lecteur.terminate();
        }
    };

    const libelle = bloc.querySelector('[data-scan-libelle]');
    bloc.querySelector('[data-scan]').addEventListener('change', async e => {
        const photo = e.target.files[0];
        if (!photo) return;
        libelle.textContent = 'Lecture en cours…';
        afficher('Lecture de l’étiquette… (quelques secondes, un peu plus si la photo est de travers)', 'info');
        try {
            await chargerTesseract();
            const composition = await lireEtiquette(photo);
            const somme = Object.values(composition).reduce((s, p) => s + p, 0);
            if (!Object.keys(composition).length) {
                afficher('Aucune matière reconnue : reprenez la photo bien à plat et nette, ou recopiez l’étiquette.', 'alerte');
            } else {
                estimee.checked = false;
                remplir(composition);
                afficher(`${Object.keys(composition).length} matière(s) reconnue(s)` + (somme === 100 ? ' : vérifiez puis enregistrez.' : ` (total ${somme} %) : complétez ou corrigez.`), somme === 100 ? 'ok' : 'alerte');
            }
        } catch (erreur) {
            afficher(erreur.message || 'La lecture a échoué : recopiez l’étiquette.', 'alerte');
        } finally {
            libelle.textContent = 'Scanner l’étiquette';
            e.target.value = '';
        }
    });

    majTotal();
})();
