# Installation (environnement de développement)

## Prérequis

- PHP **^8.2** avec les extensions habituelles de Laravel (pdo_sqlite ou pdo_mysql/pgsql selon le SGBD choisi, mbstring, openssl, tokenizer, xml, ctype, json, fileinfo)
- Composer 2
- Node.js + npm (pour la chaîne Vite, utilisée uniquement par la page `welcome` par défaut du squelette Laravel)
- Une base de données : **SQLite** par défaut (aucune installation de serveur nécessaire) ou tout SGBD supporté par Laravel (MySQL/PostgreSQL) via configuration de `.env`

## Étapes

```bash
# 1. Cloner le dépôt puis se placer dans le dossier du projet
git clone <url-du-depot> DevTrackPro
cd DevTrackPro

# 2. Installer les dépendances PHP
composer install

# 3. Copier le fichier d'environnement et générer la clé d'application
cp .env.example .env
php artisan key:generate

# 4. Créer la base de données SQLite (si vous gardez la configuration par défaut)
touch database/database.sqlite

# 5. Exécuter les migrations
php artisan migrate

# 6. (Optionnel) Peupler la base avec un utilisateur de démonstration
php artisan db:seed

# 7. Installer les dépendances front-end et builder les assets
npm install
npm run build
```

Un raccourci équivalent aux étapes 2 à 7 (hors clonage) est disponible via le script Composer défini dans `composer.json` :

```bash
composer run setup
```

## Lancer l'application en développement

Le script `composer run dev` (défini dans `composer.json`) démarre en parallèle, via `concurrently` :
- le serveur PHP intégré (`php artisan serve`) ;
- le worker de file d'attente (`php artisan queue:listen`) ;
- le suivi des logs en temps réel (`php artisan pail`) ;
- le serveur de développement Vite (`npm run dev`).

```bash
composer run dev
```

L'application est alors accessible sur `http://localhost:8000` (ou l'URL configurée par `APP_URL`).

## Variables d'environnement principales (`.env`)

Issues de `.env.example` — à adapter selon l'environnement :

| Variable | Valeur par défaut | Rôle |
|---|---|---|
| `APP_NAME` | `Laravel` | Nom de l'application (à personnaliser, ex. `DevTrack Pro`) |
| `APP_ENV` | `local` | Environnement d'exécution |
| `APP_DEBUG` | `true` | Affichage des erreurs détaillées — **à mettre à `false` en production** |
| `APP_URL` | `http://localhost` | URL de base utilisée pour générer les liens (dont ceux des notifications) |
| `DB_CONNECTION` | `sqlite` | Pilote de base de données |
| `SESSION_DRIVER` | `database` | Stockage des sessions en base |
| `QUEUE_CONNECTION` | `database` | File d'attente en base (utilisée pour les notifications `Queueable`) |
| `CACHE_STORE` | `database` | Cache applicatif |
| `MAIL_MAILER` | `log` | Les e-mails sont écrits dans les logs par défaut (aucun envoi réel) |
| `FILESYSTEM_DISK` | `local` | Disque de stockage par défaut (`storage/app/private`) |

Comme la file d'attente par défaut est `database` (et non `sync`), les notifications (`Queueable`) sont mises en file et nécessitent qu'un worker (`php artisan queue:work` ou `queue:listen`) tourne pour être effectivement traitées — c'est ce que fait `composer run dev` en local.

## Compte de démonstration

Le seeder par défaut (`database/seeders/DatabaseSeeder.php`) crée uniquement :
- e-mail : `test@example.com`
- mot de passe : `password`
- rôle : `stagiaire` (valeur par défaut de la colonne, non explicitement définie par le seeder)

Pour tester les autres rôles (`admin`, `mentor`), il faut créer des utilisateurs manuellement (via `php artisan tinker` ou en étendant le seeder), aucun compte admin/mentor n'étant fourni par défaut.
