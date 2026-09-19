# Modules fonctionnels

## Pagination

Toutes les listes tabulaires de l'application sont paginées à **5 éléments par page**, au niveau de la requête (repository, `->paginate(5)`), pas seulement à l'affichage : stages, projets, tâches, documents, journal, rapports hebdomadaires, notifications et utilisateurs. Chaque repository concerné expose une méthode `paginate...()` miroir de sa méthode `find...()`/`all()` existante (même filtre de périmètre, résultat paginé au lieu d'une collection complète), utilisée par le contrôleur correspondant. Le Kanban (tableau de cartes par statut, pas une liste tabulaire) n'est pas concerné. Les vues affichent `{{ $liste->links('pagination::bootstrap-5') }}`.

## Tableau de bord

`DashboardController::index()` → `DashboardService::indicateursPour(auth()->user())`, qui retourne des indicateurs différents selon le rôle. Le tableau de bord porte aussi les points d'entrée rapides « Créer un projet » / « Créer une tâche » pour le mentor, et le bloc « Mon stage » (mentor associé, période) pour le stagiaire.

| Rôle | Indicateurs |
|---|---|
| `admin` | `stages_actifs`, `total_utilisateurs`, `total_stagiaires`, `total_mentors`, `total_stages`, `stages_en_cours`, `stages_termines`, `stages_annules`, `total_projets`, `total_taches` |
| `mentor` | `rapports_recus`, `mes_stagiaires` (stagiaires distincts encadrés), `mes_stages`, `stages_en_cours`, `mes_projets`, `mes_taches`, `taches_en_cours`, `taches_terminees`, `taches_en_retard` (échéance dépassée et statut ≠ `termine`, calculé en mémoire sur la collection déjà chargée) |
| `stagiaire` | `stage` et `mentor` (objets du stage actif, pour l'affichage « Mon stage »), `mes_projets`, `taches_a_faire`, `taches_en_cours`, `taches_en_revue`, `taches_terminees`, `mes_documents` |

Vue : `dashboard/index.blade.php`.

## Gestion des stages

- `GET /stages` (`StageController::index`) : liste **filtrée par périmètre et paginée** — admin voit tous les stages (`paginateAll`), mentor voit ses stages (`paginateForMentor`, avec filtres statut/dates appliqués **dans la requête**), stagiaire voit l'historique de son propre stage (`paginateForStagiaire`). Affiche une colonne **Email** du stagiaire (relation `stagiaire.email`, déjà chargée).
- Un bloc **« Stagiaires actifs »** (stages au statut `en_cours`) est affiché au-dessus du tableau pour l'admin (`StageRepository::findActifs()`) et le mentor (ses propres stages actifs) — **jamais** pour un stagiaire, qui ne doit pas obtenir une liste d'autres stagiaires.
- `GET /stages/create` (`StagePolicy::create` → admin) : formulaire de création, alimenté par la liste des utilisateurs de rôle `mentor` et `stagiaire`.
- `POST /stages` (`StoreStageRequest` → `StageService::creer()`) : crée un stage après vérification qu'aucun stage actif (`planifie` ou `en_cours`) n'existe déjà pour le stagiaire choisi. Déclenche l'événement `StageCreated` (notifie le mentor et le stagiaire de leur affectation).
- `PATCH /stages/{stage}/status` (`UpdateStageStatusRequest` → `StagePolicy::updateStatus`, admin uniquement) : fait évoluer le statut selon la machine à états `planifie → en_cours/annule`, `en_cours → termine/annule` (voir [Modèle de données](Modele-de-donnees#stages)). Déclenche `StageStatusChanged` (notifie mentor + stagiaire).
- `DELETE /stages/{stage}` (`StagePolicy::delete`, admin uniquement) : soft delete via `StageService::supprimer()`, refusé tant que le statut n'est pas `termine` ou `annule`. N'affecte jamais les projets/tâches liés.
- Autorisation de création : réservé à `admin` (`StagePolicy::create`, vérifié dans `StoreStageRequest::authorize()`).
- Chaque création/modification déclenche aussi `StageObserver` → écriture dans `audit_logs` (traçabilité, indépendante des notifications).

## Gestion des projets

- `GET /projects` : liste **filtrée par périmètre et paginée**, même logique que les stages (admin : `paginateAll` ; mentor : `paginateForMentor` ; stagiaire : `paginateForStagiaire`).
- `GET /stages/{stage}/projects/create` : formulaire de création lié à un stage précis, réservé au mentor du stage (`ProjectPolicy::create`).
- `GET /projects/create` (route nommée `projects.create.mentor`, raccourci depuis le tableau de bord mentor) : même formulaire, sans stage préchargé — un `<select>` liste les stages du mentor connecté. **Réservé au rôle `mentor` uniquement** : un admin ne peut pas créer de projet (règle métier), et ne peut donc plus accéder à ce formulaire (`abort_unless($isMentor)`), cohérent avec `ProjectPolicy::create` qui refusait déjà la soumission côté admin — le bouton « Nouveau projet » n'est d'ailleurs visible qu'au mentor dans `stages/index.blade.php`.
- `POST /projects` (`StoreProjectRequest` → `ProjectService::creer()`) : crée le projet après vérification que le stage est `planifie` ou `en_cours` (sinon `ValidationException`). Les deux formulaires ci-dessus soumettent à cette même route.
- `ProjectService::archiver()` existe au niveau service (met `archive` à `true`) mais **n'est appelé par aucune route/contrôleur actuellement** — fonctionnalité prête côté métier, non encore exposée côté UI.

## Tâches & Kanban

### Liste et détail
- `GET /tasks` : liste **filtrée par périmètre** — admin : toutes les tâches ; mentor : tâches des projets de ses stages (`findForMentor`) ; stagiaire : tâches de son propre stage (`findForStagiaire`). Pour chaque tâche, seules les transitions de statut réellement autorisées pour l'utilisateur connecté (`TaskService::transitionsAutoriseesPour()`) sont proposées dans le sélecteur de statut.
- `GET /tasks/{task}` : détail d'une tâche (commentaires avec auteur, pièces jointes), protégé par `TaskPolicy::view` (admin, mentor du projet, ou stagiaire du projet) — un accès par identifiant hors périmètre renvoie 403.
- `GET /tasks/create` (réservé aux rôles `admin`/`mentor`) : formulaire de création, avec un `<select>` de projets scoppé au mentor connecté (`findForMentor`) ou à tous les projets pour un admin ; accessible aussi en raccourci depuis le tableau de bord mentor.
- `POST /tasks` (`StoreTaskRequest` → `TaskService::creer()`) : création, autorisée uniquement si l'utilisateur peut modifier le projet parent (`ProjectPolicy::update`, donc le mentor du stage). Champs : `project_id`, `titre`, `description?`, `priorite` (`basse`/`moyenne`/`haute`), `date_echeance?` (doit être aujourd'hui ou dans le futur). Déclenche l'événement `TaskCreated` (notifie le stagiaire du projet).

### Vue Kanban
- `GET /projects/{project}/kanban` (`KanbanController::show`, protégé par `ProjectPolicy::view`) : regroupe les tâches du projet en 4 colonnes (`a_faire`, `en_cours`, `en_revue`, `termine`). Un accès à un projet hors périmètre renvoie 403.
- L'interface (`kanban/show.blade.php`) implémente un **glisser-déposer HTML5** restreint aux transitions réellement autorisées pour l'utilisateur connecté (`TaskService::transitionsAutoriseesPour()`, transmises à la vue via `data-transitions`) : une carte sans transition possible n'est pas `draggable`, et une colonne hors transitions autorisées n'accepte pas le dépôt (contrôle client, doublé par le contrôle serveur de `TaskPolicy::updateStatus` sur l'appel `PATCH`).

### Transitions de statut (machine à états)

Définies dans `TaskService::TRANSITIONS_AUTORISEES` :

```
a_faire   → en_cours
en_cours  → en_revue | a_faire
en_revue  → termine  | en_cours
termine   → (aucune transition)
```

Toute transition hors de cette table lève une `ValidationException`. En complément, `TaskPolicy::updateStatus()` restreint **qui** peut effectuer quelle transition :
- `admin` ou mentor du projet : toutes les transitions autorisées par la machine à états ;
- stagiaire du projet : toutes sauf le passage à `termine` (il ne peut pas clôturer lui-même une tâche) ;
- toute autre personne : refusé.

`TaskService::transitionsAutoriseesPour(Task $task, User $user)` expose cette même règle aux vues (liste des tâches, Kanban) pour n'afficher/permettre que les actions réellement autorisées — la policy reste la seule autorité côté serveur, cette méthode ne fait qu'éviter de dupliquer la règle dans le Blade.

`PATCH /tasks/{task}/status` (`UpdateTaskStatusRequest` → contrôle `can('updateStatus', ...)` → `TaskService::changerStatut()`) déclenche, en cas de succès, l'événement `TaskStatusChanged`.

### Pièces jointes
- `POST /tasks/{task}/attachments` (`StoreAttachmentRequest` → `TaskService::ajouterPieceJointe()`) : upload d'un fichier (5 Mo max, types `pdf,doc,docx,png,jpg,jpeg`), stocké sur le disque `local` (`storage/app/private/attachments/`), nom d'origine conservé en base.

### Commentaires
Le modèle `Comment` et sa table existent (voir [Modèle de données](Modele-de-donnees#comments)), mais **aucune route ni contrôleur ne les exploite actuellement**.

## Journal de bord (stagiaire)

- `GET /journal` : liste des entrées du stagiaire connecté, triées par date décroissante.
- `POST /journal` (`JournalService::enregistrer()`) : une entrée par jour et par stagiaire (contrainte unique `(stagiaire_id, date)`) ; si une entrée existe déjà pour la date soumise, elle est **mise à jour** (upsert) plutôt que dupliquée. Contenu limité à 2000 caractères.

### Rappel automatique
La commande planifiée `journal:remind` (voir [Événements et notifications](Evenements-notifications-taches-planifiees)) journalise chaque jour à 18h les stagiaires actifs n'ayant pas encore rempli leur entrée du jour.

## Rapports hebdomadaires

- `GET /reports` : liste **paginée** des rapports du stagiaire connecté, triés par semaine décroissante.
- `POST /reports` (`ReportService::soumettre()`) : soumission d'un rapport pour une semaine (1 à 12) avec un contenu texte (max 5000 caractères). **Un rapport déjà soumis pour cette semaine bloque toute nouvelle soumission** : `ValidationException` sur le champ `semaine` avec un message explicatif, sans toucher au rapport existant. La contrainte `UNIQUE(stagiaire_id, semaine)` existe en base (migration `2026_08_26_122403_...`) en défense en profondeur. Sinon, un **PDF est généré** (`barryvdh/laravel-dompdf`, vue `reports/pdf.blade.php`) et stocké sur le disque `local` sous `reports/rapport-{stagiaire_id}-semaine{semaine}.pdf`. L'événement `WeeklyReportSubmitted` est ensuite émis, notifiant le mentor du stagiaire.
- `GET /reports/{id}/download` : téléchargement du PDF, autorisé uniquement si `WeeklyReportPolicy::view` (auteur, admin, ou mentor du stage du stagiaire).
- Les colonnes `statut` (`soumis`/`valide`/`a_corriger`) et `commentaire_mentor` existent en base et dans le modèle mais **ne sont pilotables par aucune route actuellement** — la validation/correction d'un rapport par un mentor n'est pas encore implémentée côté interface.

## Documents

- `GET /documents` : liste **paginée** des documents déposés par l'utilisateur connecté.
- `POST /documents` (`StoreDocumentRequest` → `DocumentService::deposer()`) : dépose un nouveau document (titre, catégorie optionnelle **choisie dans la liste fermée `Document::CATEGORIES`** — « Documents d'analyse », « Compte rendu », « Convention de stage », validée par `Rule::in(...)` — fichier 5 Mo max, types `pdf,doc,docx,png,jpg,jpeg`) et crée sa première version (`numero_version = 1`).
- `DocumentService::ajouterVersion()` : ajoute une nouvelle version à un document existant en incrémentant `numero_version` — méthode disponible au niveau service mais **non exposée par une route dédiée** (pas de `POST /documents/{document}/versions`).
- `GET /documents/{documentId}/versions/{versionId}/download` : téléchargement d'une version précise, autorisé si `DocumentPolicy::view` (propriétaire ou admin).

## Notifications

- `GET /notifications` (`DashboardController::notifications`) : affiche les notifications (`auth()->user()->notifications()->latest()->paginate(5)`) de l'utilisateur connecté, les plus récentes d'abord, **paginées**.
- `DELETE /notifications/{notification}` (`DashboardController::deleteNotification`) : supprime une notification. Chaque ligne de la table `notifications` appartient à un seul destinataire (`notifiable_type`/`notifiable_id`, une ligne par appel de `notify()`) : la suppression ne peut donc jamais affecter un autre utilisateur. Le contrôleur vérifie explicitement `notifiable_id === auth()->id()` avant suppression (403 sinon).
- Les notifications sont produites par le système d'événements (voir [Événements, notifications et tâches planifiées](Evenements-notifications-taches-planifiees)) : changement de statut de tâche, soumission de rapport hebdomadaire, création d'un stage (mentor + stagiaire), création d'une tâche (stagiaire du projet), création d'un utilisateur (tous les admins).

## Gestion des utilisateurs

- `GET /users` (`UserController::index`, `UserPolicy::viewAny` → `admin` uniquement) : liste **triable** (nom, e-mail, rôle, statut, date de création — `?sort=&direction=`), **paginée** (5/page, `UserRepository::paginateSorted()`) et **recherchable** (`?search=`, filtre `LIKE` sur nom/e-mail/rôle).
  - La recherche est **dynamique (AJAX)** : la saisie dans le champ de recherche déclenche, après un court délai (~350 ms), un appel `fetch()` vers la même route avec l'en-tête `X-Requested-With: XMLHttpRequest` ; le contrôleur détecte cette requête (`$request->ajax()`) et retourne uniquement le fragment HTML du tableau (`users/_table.blade.php`), remplacé dans la page sans rechargement complet. Une nouvelle recherche repart toujours de la page 1. Le formulaire reste utilisable sans JavaScript (soumission classique du `<form method="GET">`).
- `POST /users` (`UserController::store`, `UserPolicy::create` → `admin` uniquement) : crée un utilisateur (`name`, `email` unique, `password` ≥ 8 caractères haché automatiquement via le cast `hashed`, `role` parmi `admin`/`mentor`/`stagiaire`). Déclenche l'événement `UserCreated` (notifie tous les autres admins).
- `GET /users/{user}/edit` / `PUT /users/{user}` (`UserController::edit`/`update`, `UserPolicy::update`) : modification du nom, de l'e-mail, du rôle, du statut actif, et éventuellement du mot de passe (`UpdateUserRequest` ; le mot de passe n'est changé que si un nouveau est fourni).
- `DELETE /users/{user}` (`UserController::destroy`, `UserPolicy::delete` → `admin` uniquement) : **désactivation logique** (`UserService::desactiver()`, `is_active = false`), jamais de suppression physique — un `User` est référencé par clé étrangère en cascade depuis `Stage`, `JournalEntry`, `WeeklyReport` et `Document` ; une suppression réelle effacerait tout l'historique associé. Un admin ne peut pas se désactiver lui-même (`ValidationException`). La réactivation se fait en rouvrant le formulaire d'édition et en recochant « Actif » (pas de route dédiée).
- Couvert par `tests/Feature/UserManagementTest.php` (création par admin pour chaque rôle, hachage du mot de passe, refus pour mentor/stagiaire, e-mail dupliqué refusé, listing réservé à l'admin, tri, pagination à 5, recherche classique et AJAX, modification, désactivation, auto-désactivation refusée).

## Audit des privilèges

`tests/Feature/AutorisationMatriceTest.php` consolide les vérifications d'accès direct par identifiant (URL manipulée) qui ne rentrent pas naturellement dans les fichiers de test par module : visiteur non authentifié redirigé vers `/login`, stagiaire/mentor hors périmètre refusés sur tâche/kanban par ID, pages réservées à l'admin (`/users`, `/stages/create`) inaccessibles aux autres rôles.
