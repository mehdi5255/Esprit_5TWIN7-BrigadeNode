/*
 * Composition d'un vêtement (formulaire « Ajouter / modifier un vêtement »).
 *  - lignes « matière + pourcentage » ajoutées ou retirées, total affiché en direct ;
 *  - « Je n'ai plus l'étiquette » : composition et poids typiques de la catégorie ;
 *  - « Scanner l'étiquette » : lecture de la photo dans le navigateur (OCR Tesseract.js, rien n'est envoyé
 *    au serveur), puis reconnaissance des matières par leurs noms et abréviations (CO, PES, cotton…) ;
 *  - « Scanner un QR code » : passeport textile d'un vêtement TextileCycle (composition, poids, nom)
 *    ou QR code de marque contenant la composition en texte.
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

    const HAN = /\p{Script=Han}/u;

    /** Caractères que l'OCR confond souvent sur les étiquettes chinoises (氮纶 n'existe pas : c'est 氨纶). */
    const CONFUSIONS_CHINOIS = { '氮': '氨' };

    /** Un seul caractère diffère (même longueur) : « 聚柄纤维 » lu pour « 聚酯纤维 ». */
    const presque = (mot, cle) => mot.length === cle.length && [...mot].filter((c, i) => c !== cle[i]).length === 1;

    /**
     * Matière désignée par un mot chinois : le plus long nom chinois du catalogue contenu dans le mot
     * (« 人造棉 » est de la viscose, pas du coton « 棉 »). À défaut, un nom d'au moins 3 caractères
     * dont un seul a été mal lu.
     */
    const reconnaitreChinois = mot => {
        mot = [...mot].map(c => CONFUSIONS_CHINOIS[c] || c).join('');
        let trouvee = null, longueur = 0;
        for (const m of catalogue) {
            for (const cle of m.mots) {
                if (HAN.test(cle) && cle.length > longueur && mot.includes(cle)) [trouvee, longueur] = [m, cle.length];
            }
        }
        return trouvee || catalogue.find(m => m.mots.some(cle => HAN.test(cle) && cle.length >= 3 && presque(mot, cle)));
    };

    /** Trouve la matière désignée par un mot de l'étiquette (nom, abréviation ou autre langue). */
    const reconnaitre = mot => {
        mot = normaliser(mot).trim();
        if (HAN.test(mot)) return reconnaitreChinois(mot);
        if (mot.length < 2) return null;
        return catalogue.find(m => m.mots.includes(mot))
            || catalogue.find(m => m.mots.some(cle => cle.length >= 4 && (mot.startsWith(cle) || cle.startsWith(mot) && mot.length >= 4)));
    };

    /**
     * « 80% CO 20% PES », « 80 % coton / cotton » (pourcentage avant) ou « Coton 80 % » (après)
     * → { matiere_id: pourcentage }. Les deux lectures sont tentées ; on garde celle qui fait 100 %.
     */
    const analyser = texte => {
        // L'OCR lit parfois « 8o% » pour « 80% » ; en chinois, il sépare les caractères (« 聚 酯 纤 维 ») et
        // les étiquettes utilisent le « ％ » et le « ： » pleine largeur.
        const propre = normaliser(texte).replace(/％/g, '%').replace(/[：，、；]/g, ' ')
            .replace(/(\p{Script=Han})[ \t]+(?=\p{Script=Han})/gu, '$1')
            .replace(/[|/\\,;]/g, ' ').replace(/(\d)o\s*%/g, '$10%');
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
        const avant = lire(/(\d{1,3})\s*%\s*([a-z\p{Script=Han}]+(?:\s+[a-z]+)?)/gu, true);
        const apres = lire(/([a-z\p{Script=Han}]+)\s*[:\-]?\s*(\d{1,3})\s*%/gu, false);
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

    /**
     * Lit la photo sous plusieurs angles ; s'arrête dès qu'une lecture donne une composition à 100 %.
     * D'abord en français et anglais ; sans résultat complet, une seconde passe lit le chinois
     * (étiquettes « Made in China » : 棉 70%…), dont le modèle n'est téléchargé que dans ce cas.
     * Le chinois se lit mieux sur la photo d'origine : le contraste renforcé empâte ses traits fins.
     */
    const lireEtiquette = async photo => {
        const image = await createImageBitmap(photo);
        const tournee = angle => () => preparer(image, angle);
        const passes = [
            ['fra+eng', [0, -10, 10, -18, 18].map(tournee)],
            ['chi_sim+eng', [() => photo, ...[0, -10, 10].map(tournee)]],
        ];
        let meilleure = {};
        for (const [langues, images] of passes) {
            if (langues.startsWith('chi')) afficher('Lecture de l’étiquette… (essai en chinois)', 'info');
            const lecteur = await Tesseract.createWorker(langues);
            try {
                for (const variante of images) {
                    const { data } = await lecteur.recognize(variante());
                    const composition = analyser(data.text);
                    const somme = Object.values(composition).reduce((s, p) => s + p, 0);
                    if (somme === 100) return composition;
                    if (Object.keys(composition).length > Object.keys(meilleure).length) meilleure = composition;
                }
            } finally {
                await lecteur.terminate();
            }
        }
        return meilleure;
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

    // ── Scan d'un QR code ────────────────────────────────────────────────
    // Lu dans le navigateur (détecteur natif si disponible, sinon jsQR) : passeport textile TextileCycle,
    // ou tout QR code contenant une composition en texte (« 80% coton, 20% polyester »).
    const chargerJsQR = () => window.jsQR ? Promise.resolve() : new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js';
        script.onload = resolve;
        script.onerror = () => reject(new Error('Impossible de charger le lecteur de QR code (connexion internet requise).'));
        document.head.appendChild(script);
    });

    const chargerImage = photo => new Promise((resolve, reject) => {
        const image = new Image();
        image.onload = () => resolve(image);
        image.onerror = () => reject(new Error('Cette image ne peut pas être lue.'));
        image.src = URL.createObjectURL(photo);
    });

    /** Texte du QR code de la photo, ou null. Essaie plusieurs tailles : jsQR lit mal les photos trop grandes. */
    const lireQr = async photo => {
        const image = await chargerImage(photo);
        if ('BarcodeDetector' in window) {
            try {
                const [code] = await new BarcodeDetector({ formats: ['qr_code'] }).detect(image);
                if (code) return code.rawValue;
            } catch (e) { /* détecteur natif indisponible : on passe à jsQR */ }
        }
        await chargerJsQR();
        const cote = Math.max(image.naturalWidth || image.width, image.naturalHeight || image.height) || 800;
        for (const max of [1000, 1600, 600]) {
            const echelle = Math.min(1, max / cote);
            const toile = document.createElement('canvas');
            toile.width = Math.round((image.naturalWidth || image.width || 800) * echelle);
            toile.height = Math.round((image.naturalHeight || image.height || 800) * echelle);
            const ctx = toile.getContext('2d');
            ctx.fillStyle = '#fff';
            ctx.fillRect(0, 0, toile.width, toile.height);
            ctx.drawImage(image, 0, 0, toile.width, toile.height);
            const pixels = ctx.getImageData(0, 0, toile.width, toile.height);
            const code = jsQR(pixels.data, toile.width, toile.height, { inversionAttempts: 'attemptBoth' });
            if (code) return code.data;
        }
        return null;
    };

    const libelleQr = bloc.querySelector('[data-qr-libelle]');
    bloc.querySelector('[data-scan-qr]').addEventListener('change', async e => {
        const photo = e.target.files[0];
        if (!photo) return;
        libelleQr.textContent = 'Lecture en cours…';
        afficher('Lecture du QR code…', 'info');
        try {
            const texte = await lireQr(photo);
            if (!texte) {
                afficher('Aucun QR code trouvé : cadrez-le bien, à plat et net, puis réessayez.', 'alerte');
                return;
            }
            const composition = analyser(texte);
            const somme = Object.values(composition).reduce((s, p) => s + p, 0);
            if (!Object.keys(composition).length) {
                afficher(/^https?:\/\//i.test(texte.trim())
                    ? 'Ce QR code renvoie vers un site web, pas vers une composition : ouvrez-le pour la lire, puis recopiez-la.'
                    : 'QR code lu, mais il ne contient pas de composition reconnue.', 'alerte');
                return;
            }
            estimee.checked = false;
            remplir(composition);
            // Passeport textile : poids et nom du vêtement aussi (le nom seulement s'il n'est pas déjà saisi).
            const poidsLu = texte.match(/poids\s*:?\s*(\d{2,5})\s*g/i);
            if (poidsLu) poids.value = poidsLu[1];
            const nomLu = texte.match(/^nom\s*:\s*(.+)$/im);
            const nom = formulaire.querySelector('[name=nom]');
            if (nomLu && nom && !nom.value.trim()) nom.value = nomLu[1].trim();
            afficher(`QR code lu : ${Object.keys(composition).length} matière(s)` + (poidsLu ? ' et le poids' : '') + (somme === 100 ? ' — vérifiez puis enregistrez.' : ` (total ${somme} %) — complétez ou corrigez.`), somme === 100 ? 'ok' : 'alerte');
        } catch (erreur) {
            afficher(erreur.message || 'La lecture du QR code a échoué.', 'alerte');
        } finally {
            libelleQr.textContent = 'Scanner un QR code';
            e.target.value = '';
        }
    });

    majTotal();
})();
