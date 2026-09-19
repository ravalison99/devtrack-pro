# DevTrack Pro

**DevTrack Pro** est une application web Laravel de suivi de stages professionnels. Elle permet à un organisme de formation ou une entreprise de piloter, sur une interface unique, tout le cycle de vie d'un stage : affectation d'un mentor à un stagiaire, gestion de projets et de tâches en mode Kanban, journal de bord quotidien, rapports hebdomadaires exportés en PDF, gestion documentaire versionnée, notifications et traçabilité des actions sensibles.

> 📖 La documentation technique complète (architecture, modèle de données, autorisation, modules fonctionnels, routes, tests, déploiement, sécurité) est disponible dans le [Wiki du projet](../../wiki) — sources dans [`docs/wiki/`](docs/wiki).

## Sommaire

- [Fonctionnalités](#fonctionnalités)
- [Rôles applicatifs](#rôles-applicatifs)
- [Stack technique](#stack-technique)
- [Architecture en bref](#architecture-en-bref)
- [Installation](#installation)
- [Lancer l'application](#lancer-lapplication)
- [Tests](#tests)
- [Structure du projet](#structure-du-projet)
- [Documentation](#documentation)
- [Sécurité](#sécurité)
- [Licence](#licence)

## Fonctionnalités

- **Gestion des stages** : création d'un stage reliant un stagiaire à un mentor, avec dates, statut (`planifie`, `en_cours`, `termine`, `annule`) et mode de travail (`presentiel`, `hybride`, `teletravail`). Un stagiaire ne peut avoir qu'un seul stage actif à la fois.
- **Projets et tâches** : chaque stage peut porter plusieurs projets, eux-mêmes découpés en tâches (priorité, échéance, statut).
- **Tableau Kanban** : visualisation et changement de statut des tâches par glisser-déposer, avec une machine à états qui contraint les transitions autorisées et des règles d'autorisation différenciées par rôle.
- **Journal de bord** : les stagiaires consignent une entrée quotidienne ; une commande planifiée rappelle chaque jour les entrées manquantes.
- **Rapports hebdomadaires** : soumission d'un rapport texte par semaine, converti automatiquement en PDF téléchargeable, avec notification du mentor.
- **Gestion documentaire versionnée** : dépôt de documents avec historique de versions numérotées.
- **Notifications** : notifications e-mail et en base à chaque changement de statut de tâche ou soumission de rapport.
- **Audit** : traçabilité automatique des créations/modifications de stage dans un journal d'audit dédié.
- **Gestion des utilisateurs** : création de comptes (admin, mentor, stagiaire) par un administrateur, avec mots de passe hachés.
- **Sécurité** : limitation des tentatives de connexion (protection brute-force), autorisation fine par Policies Laravel sur chaque action sensible.

## Rôles applicatifs

| Rôle | Périmètre |
|---|---|
| **admin** | Accès complet : gestion des stages, des utilisateurs, visibilité sur l'ensemble des projets et rapports |
| **mentor** | Gère les projets/tâches des stages dont il est responsable, consulte les rapports de ses stagiaires |
| **stagiaire** | Gère ses tâches (dans les limites autorisées), son journal, ses rapports hebdomadaires, ses documents |

Détail complet des règles d'autorisation : [page Authentification et autorisation du wiki](../../wiki/Authentification-et-autorisation).

## Stack technique

| Composant | Technologie |
|---|---|
| Framework | Laravel 12 (PHP ^8.2) |
| Base de données | SQLite par défaut (compatible MySQL/PostgreSQL) |
| Rendu | Blade (server-side rendering) |
| UI | Bootstrap 5 + Bootstrap Icons (CDN) |
| Génération PDF | `barryvdh/laravel-dompdf` |
| Authentification | Sessions Laravel, service maison (sans Breeze/Fortify) |
| Tests | PHPUnit 11 |
| Build front-end | Vite + TailwindCSS (scaffold par défaut, non branché sur l'UI actuelle) |

## Architecture en bref

L'application suit une architecture en couches : **Contrôleur → Form Request (validation/autorisation) → Service (règles métier) → Repository (interface + implémentation Eloquent) → Modèle**. L'autorisation transversale est assurée par des **Policies** Laravel auto-découvertes par convention. Les effets de bord (notifications, audit) sont découplés via des **Events/Listeners** et un **Observer** Eloquent.

```
Requête → Middleware (auth) → Contrôleur → Form Request → Service → Repository → Modèle → BDD
                                              ↓                ↓
                                        autorisation       événements → notifications
                                        (Policies)          observer  → audit_logs
```

Détail complet : [page Architecture du wiki](../../wiki/Architecture).

## Installation

### Prérequis

- PHP ^8.2, Composer 2, Node.js/npm
- Une base de données (SQLite suffit, aucune installation de serveur requise)

### Mise en place

```bash
git clone <url-du-depot> DevTrackPro
cd DevTrackPro

composer install
cp .env.example .env
php artisan key:generate

touch database/database.sqlite
php artisan migrate
php artisan db:seed   # optionnel — crée un utilisateur de démonstration (test@example.com / password)

npm install
npm run build
```

Ces étapes sont résumées par le script `composer run setup`. Guide complet, y compris les variables d'environnement et la création de comptes admin/mentor : [page Installation du wiki](../../wiki/Installation).

## Lancer l'application

```bash
composer run dev
```

Cette commande démarre en parallèle le serveur PHP, le worker de file d'attente, le suivi des logs et le serveur Vite. L'application est accessible sur `http://localhost:8000`.

## Tests

```bash
composer run test
# ou
php artisan test
```

La suite de tests (PHPUnit, base SQLite en mémoire) couvre chaque module fonctionnel dans `tests/Feature/` : authentification, sécurité (rate limiting, audit), stages, projets, tâches, Kanban, journal, rapports, documents, notifications, gestion des utilisateurs. Détail : [page Tests du wiki](../../wiki/Tests).

## Structure du projet

```
app/
├── Console/Commands/     Commandes Artisan (rappel de journal)
├── Events/               Événements applicatifs
├── Http/
│   ├── Controllers/      Contrôleurs HTTP (fins, délèguent aux services)
│   ├── Middleware/       Middleware (EnsureRole)
│   └── Requests/         Form Requests (validation + autorisation)
├── Listeners/            Écouteurs d'événements (déclenchent les notifications)
├── Models/               Modèles Eloquent
├── Notifications/        Notifications (mail + base de données)
├── Observers/            Observers Eloquent (audit)
├── Policies/             Règles d'autorisation par modèle
├── Providers/            Fournisseurs de services (bindings de repositories, observers)
├── Repositories/
│   ├── Contracts/        Interfaces de repository
│   └── Eloquent/         Implémentations Eloquent
└── Services/             Logique métier

database/
├── factories/            Fabriques de test
├── migrations/           Schéma de base de données
└── seeders/               Données de démonstration

resources/views/          Vues Blade (layouts, partials, une vue par module)
routes/web.php            Déclaration de toutes les routes HTTP
tests/                    Suite de tests PHPUnit (Feature + Unit)
docs/wiki/                Sources de la documentation technique (voir ci-dessous)
```

## Documentation

La documentation technique complète est disponible dans le [Wiki GitHub du projet](../../wiki), et ses sources versionnées dans [`docs/wiki/`](docs/wiki) :

- [Architecture](docs/wiki/Architecture.md)
- [Modèle de données](docs/wiki/Modele-de-donnees.md)
- [Authentification et autorisation](docs/wiki/Authentification-et-autorisation.md)
- [Modules fonctionnels](docs/wiki/Modules-fonctionnels.md)
- [Routes et contrôleurs](docs/wiki/Routes-et-API.md)
- [Événements, notifications et tâches planifiées](docs/wiki/Evenements-notifications-taches-planifiees.md)
- [Installation](docs/wiki/Installation.md)
- [Tests](docs/wiki/Tests.md)
- [Déploiement](docs/wiki/Deploiement.md)
- [Sécurité](docs/wiki/Securite.md)

Voir [`docs/wiki/README.md`](docs/wiki/README.md) pour la procédure de publication de ces pages dans le Wiki GitHub.

## Sécurité

- Mots de passe hachés (Bcrypt), jamais stockés en clair.
- Limitation des tentatives de connexion (5 échecs / IP, blocage 60s) pour se prémunir des attaques par force brute.
- Autorisation systématique par Policies Laravel sur chaque action sensible (création, consultation, téléchargement).
- Upload de fichiers restreint en taille (5 Mo) et en type (`pdf`, `doc`, `docx`, `png`, `jpg`, `jpeg`), stockage hors de la racine publique.
- Journal d'audit automatique sur le cycle de vie des stages.

Détail des mesures implémentées et des limites connues : [page Sécurité du wiki](../../wiki/Securite).

## Licence

Projet construit sur le framework [Laravel](https://laravel.com), open-source sous licence [MIT](https://opensource.org/licenses/MIT).
