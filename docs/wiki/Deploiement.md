# Déploiement

⚠️ Le dépôt ne contient **aucun fichier d'infrastructure** (pas de `Dockerfile`, pas de workflow CI/CD sous `.github/workflows`, pas de configuration Sail personnalisée au-delà de la dépendance `laravel/sail` en dev). Cette page décrit donc la procédure de déploiement standard d'une application Laravel 12, adaptée aux spécificités réellement présentes dans le code (disque de stockage privé, file d'attente en base, tâche planifiée).

## Pré-requis serveur

- PHP **≥ 8.2** avec extensions : `pdo`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, plus le pilote de base de données choisi (`pdo_sqlite`, `pdo_mysql` ou `pdo_pgsql`)
- Composer 2
- Node.js/npm (uniquement au moment du build des assets — non requis à l'exécution)
- Un serveur web (Nginx/Apache) pointant sur le dossier `public/` de l'application
- Un SGBD de production si vous ne conservez pas SQLite (`DB_CONNECTION=mysql` ou `pgsql` dans `.env`)
- Un mécanisme de **cron** pour exécuter le planificateur Laravel (voir plus bas — nécessaire pour `journal:remind`)
- Un **worker de file d'attente** persistant (les notifications `TaskStatusChangedNotification` et `WeeklyReportSubmittedNotification` sont `Queueable` et la file par défaut est `database`, donc rien n'est envoyé tant qu'un worker ne les traite pas)

## Procédure de mise en production

```bash
# 1. Récupérer le code
git clone <url-du-depot> && cd DevTrackPro

# 2. Dépendances PHP en mode production (sans les paquets de dev)
composer install --no-dev --optimize-autoloader

# 3. Configuration d'environnement
cp .env.example .env
# éditer .env : APP_ENV=production, APP_DEBUG=false, APP_URL=<url réelle>,
# renseigner DB_*, MAIL_*, et éventuellement passer FILESYSTEM_DISK sur un disque durable/partagé
php artisan key:generate

# 4. Base de données
php artisan migrate --force

# 5. Assets front-end
npm ci
npm run build

# 6. Optimisations Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

Le script `composer run setup` (voir `composer.json`) automatise les étapes 2 à 5 pour un premier déploiement, mais utilise `migrate --force` sans `--no-dev` : à adapter pour un environnement de production strict.

## Lien symbolique de stockage

L'application ne référence pas de disque `public` pour les fichiers utilisateurs (documents, pièces jointes, rapports) : tous sont écrits sur le disque `local`, dont la racine est `storage/app/private` (`config/filesystems.php`), et servis exclusivement via des routes de téléchargement authentifiées (`Storage::disk('local')->download(...)`), jamais par une URL publique directe. **Aucun `php artisan storage:link` n'est donc nécessaire** pour le fonctionnement actuel de l'application.

Assurez-vous cependant que le dossier `storage/` (et `bootstrap/cache/`) est accessible en écriture par l'utilisateur du serveur web, et que son contenu est **persistant** entre les déploiements (volume dédié, ou hors du dossier de build applicatif) puisqu'il contient les fichiers utilisateurs déposés (`documents/`, `attachments/`, `reports/`) ainsi que, en SQLite, la base de données elle-même.

## Planificateur et files d'attente

Deux processus doivent tourner en continu (superviseur de type `systemd`, Supervisor, ou équivalent) :

```bash
# Traitement des notifications en file d'attente
php artisan queue:work --tries=1

# Planificateur Laravel — à invoquer chaque minute par cron
* * * * * cd /chemin/vers/DevTrackPro && php artisan schedule:run >> /dev/null 2>&1
```

Sans cette entrée cron, la commande planifiée `journal:remind` (exécution quotidienne à 18h, voir [Événements et notifications](Evenements-notifications-taches-planifiees)) ne se déclenchera jamais. Sans worker de file d'attente actif, les notifications par e-mail/base liées aux changements de statut de tâche et aux soumissions de rapport resteront en attente indéfiniment.

## Base de données en production

SQLite (choix par défaut de `.env.example`) convient à une démonstration ou un déploiement à faible concurrence, mais n'est pas recommandé pour une charge de production significative (écritures concurrentes limitées). Pour un déploiement réel, il est recommandé de basculer vers MySQL ou PostgreSQL en renseignant `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` dans `.env` — aucune migration du projet n'utilise de syntaxe SQL spécifique à SQLite, la bascule est donc directe.

## E-mails

`MAIL_MAILER=log` par défaut : en production, configurer un vrai transport (SMTP, ou tout driver supporté par Laravel) sous peine que les notifications par e-mail (changement de statut de tâche, soumission de rapport) soient uniquement écrites dans les logs applicatifs plutôt qu'envoyées aux destinataires.

## Points de vigilance spécifiques à ce projet

- `APP_DEBUG` doit impérativement être `false` en production (sans quoi la page d'erreur Laravel expose la stack trace et des informations de configuration).
- Le mot de passe de connexion transite en clair côté formulaire HTML (`resources/views/auth/login.blade.php`) : le déploiement **doit** être servi en HTTPS (non configuré dans le code applicatif, à la charge du reverse proxy/serveur web).
- La limitation de tentatives de connexion (`RateLimiter`, voir [Sécurité](Securite)) se base sur `$request->ip()` : si l'application est déployée derrière un reverse proxy/load balancer, configurer les « trusted proxies » Laravel (`bootstrap/app.php` / middleware `TrustProxies`, non configuré actuellement) pour que l'IP réelle du client soit correctement détectée plutôt que celle du proxy.
