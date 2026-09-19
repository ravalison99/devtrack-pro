# Publier ce dossier dans le Wiki GitHub

Ce dossier contient l'intégralité de la documentation technique de DevTrack Pro, écrite au format attendu par un **GitHub Wiki** (un fichier = une page, le nom de fichier devient le nom de la page, `_Sidebar.md` alimente le menu latéral).

Le wiki GitHub est un dépôt Git séparé (`<votre-repo>.wiki.git`), distinct du dépôt de code. Pour y publier ces pages :

```bash
# 1. Activer le Wiki sur le dépôt GitHub (Settings > Features > Wikis),
#    puis créer au moins une page depuis l'interface web pour initialiser le dépôt wiki.

# 2. Cloner le dépôt wiki (à côté du dépôt de code, pas dedans)
git clone https://github.com/<utilisateur>/<repo>.wiki.git

# 3. Copier les pages de ce dossier dans le clone du wiki
cp docs/wiki/*.md ../<repo>.wiki/

# 4. Committer et pousser
cd ../<repo>.wiki
git add .
git commit -m "Documentation technique DevTrack Pro"
git push
```

Les pages seront alors accessibles sur `https://github.com/<utilisateur>/<repo>/wiki`.

## Pages incluses

| Fichier | Page wiki |
|---|---|
| `Home.md` | Accueil |
| `Architecture.md` | Architecture |
| `Modele-de-donnees.md` | Modèle de données |
| `Authentification-et-autorisation.md` | Authentification et autorisation |
| `Modules-fonctionnels.md` | Modules fonctionnels |
| `Routes-et-API.md` | Routes et contrôleurs |
| `Evenements-notifications-taches-planifiees.md` | Événements, notifications et tâches planifiées |
| `Installation.md` | Installation |
| `Tests.md` | Tests |
| `Deploiement.md` | Déploiement |
| `Securite.md` | Sécurité |
| `_Sidebar.md` | Menu latéral (spécial GitHub Wiki) |

Ce fichier `README.md` n'est pas destiné à être copié dans le wiki (il n'apporte rien une fois le wiki en place) ; il documente uniquement la procédure de publication.
