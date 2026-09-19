# DevTrack Pro — Documentation technique

**DevTrack Pro** est une application web Laravel de suivi de stages professionnels. Elle permet à un organisme de formation ou une entreprise de piloter le cycle de vie complet d'un stage : affectation d'un mentor à un stagiaire, gestion de projets et de tâches (Kanban), journal de bord quotidien, rapports hebdomadaires (export PDF), gestion documentaire versionnée, notifications et traçabilité (audit).

Cette documentation est générée à partir du code source du dépôt (branche `feature/s12-gestion-utilisateurs` au moment de la rédaction) et constitue la source de vérité technique du projet.

## Sommaire

| Page | Contenu |
|---|---|
| [Architecture](Architecture) | Vue d'ensemble technique, patrons de conception, organisation des couches |
| [Modèle de données](Modele-de-donnees) | Schéma relationnel, tables, migrations, relations Eloquent |
| [Authentification et autorisation](Authentification-et-autorisation) | Connexion, rôles, policies, middleware |
| [Modules fonctionnels](Modules-fonctionnels) | Description détaillée de chaque module métier |
| [Routes et contrôleurs](Routes-et-API) | Table complète des routes HTTP exposées |
| [Événements, notifications et tâches planifiées](Evenements-notifications-taches-planifiees) | Events, Listeners, Notifications, Observers, commande planifiée |
| [Installation](Installation) | Mise en place de l'environnement de développement |
| [Tests](Tests) | Stratégie et exécution de la suite de tests automatisés |
| [Déploiement](Deploiement) | Mise en production |
| [Sécurité](Securite) | Mesures de sécurité implémentées |

## Aperçu rapide du projet

- **Framework** : Laravel 12 (PHP ^8.2)
- **Rendu** : Blade côté serveur, mise en forme Bootstrap 5 (CDN)
- **Base de données** : SQLite par défaut (`database/database.sqlite`), compatible tout SGBD supporté par Laravel
- **Génération PDF** : `barryvdh/laravel-dompdf`
- **Authentification** : contrôleur/service maison basé sur les sessions Laravel (pas de package Breeze/Fortify/Jetstream)
- **Rôles applicatifs** : `admin`, `mentor`, `stagiaire`
- **Architecture applicative** : Contrôleurs → Form Requests → Services → Repositories (interfaces + implémentations Eloquent) → Modèles

Voir le [README du dépôt](https://github.com) pour une présentation générale destinée aux nouveaux contributeurs.
