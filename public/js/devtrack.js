/* DevTrack Pro — interactions légères (aucune dépendance hors Bootstrap) */
(function () {
    'use strict';

    var CLE_THEME = 'dtp-theme';

    function themeActuel() {
        return document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light';
    }

    function appliquerTheme(theme) {
        document.documentElement.setAttribute('data-bs-theme', theme);
        try { localStorage.setItem(CLE_THEME, theme); } catch (e) { /* stockage indisponible : on garde le thème de la session */ }
        document.querySelectorAll('[data-theme-toggle]').forEach(function (bouton) {
            var sombre = theme === 'dark';
            bouton.setAttribute('aria-pressed', sombre ? 'true' : 'false');
            bouton.setAttribute('title', sombre ? 'Passer en mode clair' : 'Passer en mode sombre');
            var icone = bouton.querySelector('i');
            if (icone) { icone.className = 'bi ' + (sombre ? 'bi-sun-fill' : 'bi-moon-stars-fill'); }
            var libelle = bouton.querySelector('[data-theme-libelle]');
            if (libelle) { libelle.textContent = sombre ? 'Mode clair' : 'Mode sombre'; }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        /* --- Mode sombre --- */
        appliquerTheme(themeActuel());
        document.querySelectorAll('[data-theme-toggle]').forEach(function (bouton) {
            bouton.addEventListener('click', function () {
                appliquerTheme(themeActuel() === 'dark' ? 'light' : 'dark');
            });
        });

        /* --- Notifications de succès (toasts) --- */
        if (window.bootstrap) {
            document.querySelectorAll('.toast[data-auto]').forEach(function (element) {
                new bootstrap.Toast(element, { delay: 4500 }).show();
            });
        }

        /* --- Zones de dépôt de fichier --- */
        document.querySelectorAll('[data-dropzone]').forEach(function (zone) {
            var champ = zone.querySelector('input[type="file"]');
            var nom = zone.querySelector('[data-dz-nom]');
            if (!champ) { return; }

            function afficherNom() {
                if (nom) { nom.textContent = champ.files.length ? champ.files[0].name : ''; }
            }

            champ.addEventListener('change', afficherNom);
            ['dragenter', 'dragover'].forEach(function (evenement) {
                zone.addEventListener(evenement, function (e) { e.preventDefault(); zone.classList.add('survol'); });
            });
            ['dragleave', 'drop'].forEach(function (evenement) {
                zone.addEventListener(evenement, function (e) { e.preventDefault(); zone.classList.remove('survol'); });
            });
            zone.addEventListener('drop', function (e) {
                if (e.dataTransfer && e.dataTransfer.files.length) {
                    champ.files = e.dataTransfer.files;
                    afficherNom();
                }
            });
        });

        /* --- Compteurs animés --- */
        var reduit = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        document.querySelectorAll('[data-compte]').forEach(function (element) {
            var cible = parseInt(element.getAttribute('data-compte'), 10);
            if (isNaN(cible) || reduit || cible === 0) { element.textContent = isNaN(cible) ? element.textContent : cible; return; }
            var debut = null;
            function pas(t) {
                if (debut === null) { debut = t; }
                var avancement = Math.min((t - debut) / 700, 1);
                element.textContent = Math.round(cible * (1 - Math.pow(1 - avancement, 3)));
                if (avancement < 1) { requestAnimationFrame(pas); }
            }
            requestAnimationFrame(pas);
        });

        /* --- Anneaux et barres de progression : animation à l'apparition --- */
        /* La valeur est déjà dans le HTML (lisible sans JavaScript) : on rejoue l'animation depuis zéro. */
        if (!reduit) {
            var anneaux = document.querySelectorAll('.anneau[data-valeur]');
            var barres = document.querySelectorAll('.barre-progression > span[data-largeur]');

            anneaux.forEach(function (anneau) {
                anneau.style.transition = 'none';
                anneau.style.setProperty('--p', '0');
            });
            barres.forEach(function (barre) {
                barre.style.transition = 'none';
                barre.style.width = '0%';
            });

            void document.body.offsetWidth; /* force le calcul de style avant de relancer la transition */

            requestAnimationFrame(function () {
                anneaux.forEach(function (anneau) {
                    anneau.style.transition = '';
                    anneau.style.setProperty('--p', anneau.getAttribute('data-valeur'));
                });
                barres.forEach(function (barre) {
                    barre.style.transition = '';
                    barre.style.width = barre.getAttribute('data-largeur') + '%';
                });
            });
        }

        /* --- Afficher / masquer un mot de passe --- */
        document.querySelectorAll('[data-voir-mdp]').forEach(function (bouton) {
            bouton.addEventListener('click', function () {
                var champ = document.querySelector(bouton.getAttribute('data-voir-mdp'));
                if (!champ) { return; }
                var visible = champ.type === 'text';
                champ.type = visible ? 'password' : 'text';
                var icone = bouton.querySelector('i');
                if (icone) { icone.className = 'bi ' + (visible ? 'bi-eye' : 'bi-eye-slash'); }
            });
        });
    });
})();
