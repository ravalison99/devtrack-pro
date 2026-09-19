# Authentification et autorisation

## Authentification

L'authentification est **implémentée manuellement** (pas de Breeze/Fortify/Jetstream) autour du système de sessions natif de Laravel.

### Flux de connexion

1. `GET /login` → `AuthController::showLoginForm()` affiche `resources/views/auth/login.blade.php`.
2. `POST /login` → `AuthController::login()` :
   - valide `email` (requis, format email) et `password` (requis, ≥ 8 caractères) ;
   - vérifie la limitation de tentatives (voir ci-dessous) ;
   - délègue à `AuthService::authenticate()` qui recherche l'utilisateur par e-mail (`UserRepositoryInterface::findByEmail`), vérifie que `is_active` est vrai et que le mot de passe correspond (`Hash::check`), puis appelle `Auth::login($user)` ;
   - si les identifiants sont invalides ou le compte inactif, une `ValidationException` est levée (message générique « Identifiants invalides. », sans distinguer compte inexistant / mot de passe faux / compte désactivé — pour ne pas divulguer d'information) ;
   - en cas de succès, redirection vers `dashboard`.
3. `POST /logout` → `AuthController::logout()` : déconnexion (`Auth::logout()`), invalidation de session et régénération du jeton CSRF.

### Limitation des tentatives de connexion (brute-force)

Implémentée directement dans `AuthController::login()` via `Illuminate\Support\Facades\RateLimiter` :

- clé de limitation : `login:{ip_du_client}` ;
- seuil : **5 tentatives échouées** ;
- fenêtre de blocage : **60 secondes** après le 5ᵉ échec (`RateLimiter::hit($limiteur, 60)`) ;
- à la 6ᵉ tentative (et suivantes tant que le délai n'est pas écoulé), une erreur de validation est renvoyée sur le champ `email` avec le nombre de secondes restantes ;
- toute connexion réussie réinitialise le compteur (`RateLimiter::clear($limiteur)`).

Cette règle est couverte par `tests/Feature/SecurityTest.php`.

## Rôles

Le rôle est un champ enum sur `users.role` : `admin`, `mentor`, `stagiaire` (défaut `stagiaire`). Le modèle `User` expose trois méthodes de confort : `isAdmin()`, `isMentor()`, `isStagiaire()`, utilisées dans tout le code (services, policies, vues) au lieu de comparer `role` directement.

| Rôle | Vue d'ensemble des permissions |
|---|---|
| `admin` | Accès total : gestion des stages, des utilisateurs, visibilité sur tous les projets/tâches/rapports |
| `mentor` | Gère les projets/tâches des stages dont il est le mentor, consulte les rapports de ses stagiaires |
| `stagiaire` | Gère ses propres tâches (dans les limites autorisées), son journal, ses rapports hebdomadaires, ses documents |

## Autorisation

### Policies (`app/Policies`)

Laravel 12 résout automatiquement les policies par **convention de nommage** entre un modèle `App\Models\X` et une classe `App\Policies\XPolicy` — aucune déclaration explicite (`Gate::policy()`) n'est présente dans le code.

| Policy | Méthode | Règle |
|---|---|---|
| `UserPolicy` | `viewAny` | `admin` uniquement |
| | `create` | `admin` uniquement |
| | `update` | `admin`, ou l'utilisateur lui-même |
| | `updateRole` | `admin` uniquement |
| | `delete` | `admin` uniquement (l'interdiction de s'auto-désactiver est une règle métier dans `UserService`, pas dans la policy) |
| `StagePolicy` | `create` | `admin` uniquement |
| | `update` | `admin` uniquement |
| | `view` | `admin`, le mentor du stage, ou le stagiaire du stage |
| | `updateStatus` | `admin` uniquement |
| | `delete` | `admin` uniquement (le contrôle du statut éligible — `termine`/`annule` — est une règle métier dans `StageService`, pas dans la policy) |
| `ProjectPolicy` | `create($user, $stage)` | le `mentor` **du stage concerné** uniquement |
| | `update` | le `mentor` du stage auquel appartient le projet |
| | `view` | `admin`, le mentor du stage, ou le stagiaire du stage |
| `TaskPolicy` | `view` | `admin`, le mentor du projet, ou le stagiaire du projet |
| | `updateStatus($user, $task, $nouveauStatut)` | `admin`/mentor du projet : tout changement autorisé ; stagiaire du projet : tout changement **sauf** passage à `termine` ; toute autre personne : refusé |
| `WeeklyReportPolicy` | `create` | `stagiaire` uniquement |
| | `view` | `admin`, l'auteur du rapport, ou le mentor du stage de l'auteur |
| `DocumentPolicy` | `create` | tout utilisateur authentifié |
| | `view` | `admin`, ou le propriétaire du document |

**Remarque** : `updateRole` n'est invoquée par aucune route/action à ce jour (le changement de rôle passe par `update`, qui couvre l'ensemble du formulaire d'édition) — règle prête pour un futur contrôle plus fin si le changement de rôle devait un jour être isolé du reste du formulaire.

### Où l'autorisation est vérifiée

Deux mécanismes coexistent selon que l'information nécessaire est disponible au moment de la validation de la requête :

1. **Dans le Form Request**, via `authorize()`, quand la ressource concernée peut être résolue depuis les données postées :
   - `StoreProjectRequest::authorize()` : `$user->can('create', [Project::class, $stage])`
   - `StoreStageRequest::authorize()` : `$user->isAdmin()`
   - `StoreTaskRequest::authorize()` : `$user->can('update', $project)`
   - `StoreAttachmentRequest::authorize()` / `UpdateTaskStatusRequest::authorize()` : utilisateur authentifié uniquement (l'autorisation fine est faite ensuite dans le contrôleur, car elle dépend d'un paramètre de route ou d'un champ du payload)

2. **Dans le contrôleur**, via `$this->authorize()` (trait `AuthorizesRequests`) ou `auth()->user()->can()` :
   - `UserController::index()` → `authorize('viewAny', User::class)`
   - `UserController::store()` → `authorize('create', User::class)`
   - `UserController::edit()`/`update()` → `authorize('update', $user)`
   - `UserController::destroy()` → `authorize('delete', $user)`, puis `UserService::desactiver()` refuse l'auto-désactivation
   - `DashboardController::deleteNotification()` → vérifie que la notification appartient à l'utilisateur connecté (`notifiable_id === auth()->id()`), sinon 403
   - `StageController::create()` → `authorize('create', Stage::class)`
   - `StageController::destroy()` → `authorize('delete', $stage)`
   - `StageController::updateStatus()` (via `UpdateStageStatusRequest::authorize()`) → `can('updateStatus', $stage)`
   - `ProjectController::create()` → `authorize('create', [Project::class, $stage])` (formulaire lié à un stage précis) ; le formulaire sans stage préchargé (`GET /projects/create`, raccourci depuis le tableau de bord mentor) est réservé aux rôles `admin`/`mentor` par un contrôle direct (`abort_unless`), en amont du choix du stage
   - `TaskController::show()` → `authorize('view', $task)`
   - `TaskController::create()` réservé aux rôles `admin`/`mentor` (`abort_unless`), puis `authorize('update', $project)` si un projet est préchargé
   - `TaskController::updateStatus()` → `can('updateStatus', [$task, $nouveauStatut])`, avec réponse JSON 403 si `wantsJson()`
   - `KanbanController::show()` → `authorize('view', $project)`
   - `DocumentController::download()` / `WeeklyReportController::download()` → `can('view', $document|$report)`

### Filtrage par périmètre (scoping) des listes

Au-delà de l'autorisation « peut-on effectuer cette action », plusieurs listes sont **filtrées côté serveur** selon le rôle de l'utilisateur connecté, pour qu'un utilisateur ne voie jamais de données hors de son périmètre (et pas seulement via le masquage de liens côté vue) :

| Contrôleur | Admin | Mentor | Stagiaire |
|---|---|---|---|
| `TaskController::index()` | toutes les tâches | tâches des projets de ses stages (`TaskRepository::findForMentor`) | tâches de son propre stage (`findForStagiaire`) |
| `ProjectController::index()` | tous les projets | projets de ses stages (`findForMentor`) | projet(s) de son propre stage (`findForStagiaire`) |
| `StageController::index()` | tous les stages | ses stages (`findByMentor`), avec filtres statut/stagiaire/dates | historique de son propre stage (`allForStagiaire`) |

`TaskController::show()` et `KanbanController::show()` complètent ce filtrage de liste par une vérification d'autorisation sur l'accès direct par identifiant (`TaskPolicy::view`, `ProjectPolicy::view`), pour empêcher la consultation d'une ressource hors périmètre en modifiant l'URL.

### Middleware de rôle (`EnsureRole`)

`App\Http\Middleware\EnsureRole` est enregistré sous l'alias `role` dans `bootstrap/app.php` (`$middleware->alias(['role' => EnsureRole::class])`). Il vérifie que l'utilisateur authentifié possède l'un des rôles passés en paramètre, sinon renvoie une 403. **Il n'est appliqué à aucune route dans `routes/web.php`** : la restriction d'accès par rôle passe exclusivement par les Policies et le filtrage par périmètre décrits ci-dessus (et, côté vue, par un masquage conditionnel des liens de navigation dans `partials/nav.blade.php`, qui n'est qu'un confort d'UX et ne constitue pas une protection serveur).

### Masquage de la navigation par rôle (UX, pas une protection)

`resources/views/partials/nav.blade.php` conditionne l'affichage des liens :
- « Stages » : `admin` ou `mentor`
- « Projets » : tous les rôles (la page elle-même est filtrée par périmètre, voir ci-dessus)
- « Journal », « Rapports » : `stagiaire` uniquement
- « Utilisateurs » : `admin` uniquement

Ce masquage améliore l'expérience utilisateur mais **ne remplace pas** le contrôle serveur (policies + filtrage par périmètre) : une route protégée reste inaccessible même si le lien n'apparaît pas. Couvert par `tests/Feature/AutorisationMatriceTest.php`, qui vérifie systématiquement qu'un accès direct par identifiant hors périmètre est refusé, quel que soit le rôle.

## Matrice consolidée des permissions

Vue d'ensemble ressource × action × rôle, telle qu'implémentée (pas un idéal théorique — reflète exactement les policies et contrôles ci-dessus) :

| Ressource | Action | Admin | Mentor | Stagiaire |
|---|---|---|---|---|
| Utilisateurs | Consulter la liste | Oui | Non (403) | Non (403) |
| Utilisateurs | Créer | Oui | Non | Non |
| Utilisateurs | Modifier | Oui | Non | Non |
| Utilisateurs | Désactiver | Oui (sauf soi-même) | Non | Non |
| Stages | Consulter la liste | Toutes | Les siens (mentor du stage) | Son historique seul |
| Stages | Créer | Oui | Non | Non |
| Stages | Changer le statut | Oui | Non | Non |
| Stages | Supprimer (soft delete) | Oui, si `termine`/`annule` | Non | Non |
| Projets | Consulter la liste | Tous | Les siens | Ceux de son stage |
| Projets | Créer | **Non** | Oui (sur ses stages) | Non |
| Tâches | Consulter la liste / le détail | Toutes | Les siennes | Les siennes |
| Tâches | Créer | Oui | Oui (sur ses projets) | Non |
| Tâches | Changer le statut | Toute transition | Toute transition (ses tâches) | Toute transition sauf → `termine` |
| Kanban | Consulter | Tout projet | Ses projets | Ses projets |
| Documents | Consulter / déposer | Les siens (+ tout document en admin sur `view`) | Les siens | Les siens |
| Rapports hebdomadaires | Soumettre | Non | Non | Oui (1 par semaine, bloqué au-delà) |
| Rapports hebdomadaires | Télécharger | Tout rapport | Ceux de ses stagiaires | Les siens |
| Notifications | Consulter / supprimer | Les siennes | Les siennes | Les siennes |
| Journal | Consulter / écrire | — (non concerné) | — (non concerné) | Le sien |

« Non concerné » signifie que la ressource n'a de sens que pour ce rôle (ex. seul un stagiaire tient un journal).
