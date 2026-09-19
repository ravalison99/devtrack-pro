# Routes et contrôleurs

Toutes les routes sont déclarées dans `routes/web.php`. Il n'y a pas de fichier `routes/api.php` actif : l'application est entièrement servie en rendu Blade côté serveur (le point `PATCH /tasks/{task}/status` accepte toutefois une réponse JSON en fonction de l'en-tête `Accept`).

## Routes publiques

| Méthode | URI | Contrôleur → Action | Nom de route |
|---|---|---|---|
| GET | `/` | closure → vue `welcome` | — |
| GET | `/login` | `AuthController::showLoginForm` | `login` |
| POST | `/login` | `AuthController::login` | — |
| POST | `/logout` | `AuthController::logout` | `logout` |

## Routes authentifiées (middleware `auth`)

| Méthode | URI | Contrôleur → Action | Nom de route | Autorisation |
|---|---|---|---|---|
| GET | `/dashboard` | `DashboardController::index` | `dashboard` | utilisateur authentifié |
| GET | `/stages` | `StageController::index` | `stages.index` | filtré par périmètre (admin/mentor/stagiaire) |
| GET | `/stages/create` | `StageController::create` | `stages.create` | `admin` (`StagePolicy::create`) |
| POST | `/stages` | `StageController::store` | `stages.store` | `admin` (`StoreStageRequest`) |
| PATCH | `/stages/{stage}/status` | `StageController::updateStatus` | `stages.updateStatus` | `admin` (`UpdateStageStatusRequest` → `StagePolicy::updateStatus`) |
| DELETE | `/stages/{stage}` | `StageController::destroy` | `stages.destroy` | `admin` (`StagePolicy::delete`), statut `termine`/`annule` requis |
| GET | `/projects` | `ProjectController::index` | `projects.index` | filtré par périmètre (admin/mentor/stagiaire) |
| GET | `/projects/create` | `ProjectController::create` | `projects.create.mentor` | `mentor` uniquement (formulaire sans stage préchargé — l'admin ne peut pas créer de projet) |
| GET | `/stages/{stage}/projects/create` | `ProjectController::create` | `projects.create` | mentor du stage (`ProjectPolicy::create`) |
| POST | `/projects` | `ProjectController::store` | `projects.store` | mentor du stage (`StoreProjectRequest`) |
| GET | `/tasks` | `TaskController::index` | `tasks.index` | filtré par périmètre (admin/mentor/stagiaire) |
| GET | `/tasks/create` | `TaskController::create` | `tasks.create` | `admin`/`mentor` |
| GET | `/tasks/{task}` | `TaskController::show` | `tasks.show` | admin, mentor ou stagiaire du projet (`TaskPolicy::view`) |
| POST | `/tasks` | `TaskController::store` | `tasks.store` | mentor du projet (`StoreTaskRequest`) |
| PATCH | `/tasks/{task}/status` | `TaskController::updateStatus` | `tasks.updateStatus` | selon rôle + transition (`TaskPolicy::updateStatus`) |
| POST | `/tasks/{task}/attachments` | `TaskController::storeAttachment` | `tasks.attachments.store` | utilisateur authentifié (`StoreAttachmentRequest`) |
| GET | `/projects/{project}/kanban` | `KanbanController::show` | `kanban.show` | admin, mentor ou stagiaire du projet (`ProjectPolicy::view`) |
| GET | `/journal` | `JournalController::index` | `journal.index` | utilisateur authentifié (portée sur ses propres entrées) |
| POST | `/journal` | `JournalController::store` | `journal.store` | utilisateur authentifié |
| GET | `/reports` | `WeeklyReportController::index` | `reports.index` | utilisateur authentifié (portée sur ses propres rapports) |
| POST | `/reports` | `WeeklyReportController::store` | `reports.store` | utilisateur authentifié |
| GET | `/reports/{id}/download` | `WeeklyReportController::download` | `reports.download` | auteur, admin, ou mentor du stage (`WeeklyReportPolicy::view`) |
| GET | `/documents` | `DocumentController::index` | `documents.index` | utilisateur authentifié (portée sur ses propres documents) |
| POST | `/documents` | `DocumentController::store` | `documents.store` | utilisateur authentifié (`StoreDocumentRequest` → `DocumentPolicy::create`) |
| GET | `/documents/{documentId}/versions/{versionId}/download` | `DocumentController::download` | `documents.download` | propriétaire ou admin (`DocumentPolicy::view`) |
| GET | `/notifications` | `DashboardController::notifications` | `notifications.index` | utilisateur authentifié (portée sur ses propres notifications), paginé (5/page) |
| DELETE | `/notifications/{notification}` | `DashboardController::deleteNotification` | `notifications.destroy` | propriétaire de la notification uniquement |
| GET | `/users` | `UserController::index` | `users.index` | `admin` (`UserPolicy::viewAny`), triable, recherchable (AJAX) et paginé (5/page) |
| POST | `/users` | `UserController::store` | `users.store` | `admin` (`UserPolicy::create`) |
| GET | `/users/{user}/edit` | `UserController::edit` | `users.edit` | `admin` (`UserPolicy::update`) |
| PUT | `/users/{user}` | `UserController::update` | `users.update` | `admin` (`UpdateUserRequest` → `UserPolicy::update`) |
| DELETE | `/users/{user}` | `UserController::destroy` | `users.destroy` | `admin` (`UserPolicy::delete`) — désactivation logique (`is_active = false`), jamais de suppression physique ; un admin ne peut pas se désactiver lui-même |

## Commandes Artisan personnalisées

| Commande | Classe | Rôle |
|---|---|---|
| `journal:remind` | `App\Console\Commands\RemindJournalEntry` | Journalise (log) les stagiaires actifs n'ayant pas rempli leur journal du jour. Planifiée quotidiennement à 18h (`routes/console.php`, `Schedule::command(...)->dailyAt('18:00')`). |

## Route système

| URI | Origine |
|---|---|
| `GET /up` | Route de santé (« health check ») générée automatiquement par `bootstrap/app.php` (`->withRouting(health: '/up')`) |
