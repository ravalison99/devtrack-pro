# Événements, notifications, observers et tâches planifiées

Ce module décrit comment les effets de bord (notifications, audit) sont découplés de la logique métier principale.

## Événements et listeners

Laravel résout les listeners par **auto-discovery** (aucun mapping explicite dans un `EventServiceProvider` — celui-ci n'existe pas dans `app/Providers`, ce qui est la convention par défaut de Laravel 11/12 : les listeners sont détectés automatiquement via leur signature de méthode `handle()`).

### `TaskStatusChanged`

- **Émis par** : `TaskService::changerStatut()`, après une transition de statut validée.
- **Payload** : `task` (instance `Task`), `ancienStatut`, `nouveauStatut`.
- **Écouté par** : `LogTaskStatusChanged` → pour chaque destinataire non nul parmi le mentor et le stagiaire du stage du projet de la tâche, envoie `TaskStatusChangedNotification`.

### `WeeklyReportSubmitted`

- **Émis par** : `ReportService::soumettre()`, après génération du PDF.
- **Payload** : `report` (instance `WeeklyReport`).
- **Écouté par** : `LogWeeklyReportSubmitted` → notifie le mentor du stage actif du stagiaire (premier stage trouvé via `stagesEnTantQueStagiaire()->first()`) avec `WeeklyReportSubmittedNotification`. Si le stagiaire n'a pas de stage (ou pas de mentor associé), aucune notification n'est envoyée (accès sécurisé par `?->`).

### `StageCreated`

- **Émis par** : `StageService::creer()`, après création du stage.
- **Payload** : `stage` (instance `Stage`).
- **Écouté par** : `NotifyStageAssignment` → notifie le mentor **et** le stagiaire du stage (`StageAssignedNotification`), pour les informer de leur affectation mutuelle.

### `StageStatusChanged`

- **Émis par** : `StageService::changerStatut()`, après une transition de statut validée (admin uniquement).
- **Payload** : `stage`, `ancienStatut`, `nouveauStatut`.
- **Écouté par** : `NotifyStageStatusChanged` → notifie le mentor et le stagiaire du stage (`StageStatusChangedNotification`).

### `TaskCreated`

- **Émis par** : `TaskService::creer()`, après création d'une tâche.
- **Payload** : `task` (instance `Task`).
- **Écouté par** : `NotifyTaskAssignment` → notifie le stagiaire du projet auquel appartient la tâche (`TaskAssignedNotification`). Si le projet n'a pas de stagiaire associé, aucune notification n'est envoyée.

### `UserCreated`

- **Émis par** : `UserService::creer()`, après création d'un utilisateur (route `/users`, admin uniquement).
- **Payload** : `user` (instance `User` créée).
- **Écouté par** : `NotifyAdminsUserCreated` → notifie tous les utilisateurs `role = admin` **sauf** celui qui vient d'être créé le cas échéant (`NewUserNotification`).

**Hors périmètre, volontairement non implémenté** : une notification « nouveau document » n'existe pas — `Document` n'a aucun lien vers un stage/mentor dans le schéma actuel (uniquement `utilisateur_id`), ce qui empêcherait de déterminer un destinataire pertinent sans ajouter une relation non demandée par ailleurs.

## Notifications

Toutes les classes de notification implémentent `via() = ['mail', 'database']` : chaque notification est donc à la fois envoyée par e-mail et enregistrée dans la table `notifications` (consultable via `/notifications`).

| Notification | Déclenchée par | Contenu e-mail | Contenu `data` (base) |
|---|---|---|---|
| `TaskStatusChangedNotification` | `TaskStatusChanged` | « La tâche « {titre} » est passée de « {ancien} » à « {nouveau} ». » + lien vers la tâche | `task_id`, `titre`, `ancien_statut`, `nouveau_statut` |
| `WeeklyReportSubmittedNotification` | `WeeklyReportSubmitted` | « {nom du stagiaire} a soumis son rapport de la semaine {semaine}. » + lien de téléchargement | `report_id`, `stagiaire`, `semaine` |
| `StageAssignedNotification` | `StageCreated` | Annonce de l'affectation mentor ↔ stagiaire + lien vers `/stages` | `stage_id`, `stagiaire`, `mentor` |
| `StageStatusChangedNotification` | `StageStatusChanged` | « Le stage de {stagiaire} est passé de « {ancien} » à « {nouveau} ». » + lien vers `/stages` | `stage_id`, `stagiaire`, `ancien_statut`, `nouveau_statut` |
| `TaskAssignedNotification` | `TaskCreated` | Annonce de la nouvelle tâche assignée + lien vers la tâche | `task_id`, `titre`, `projet` |
| `NewUserNotification` | `UserCreated` | Annonce du nouvel utilisateur (nom, rôle) + lien vers `/users` | `user_id`, `name`, `role` |

Le canal `mail` utilise le mailer configuré (`MAIL_MAILER`, `log` par défaut en développement — les e-mails sont donc écrits dans les logs plutôt qu'envoyés réellement, sauf configuration SMTP). Toutes les notifications sont couvertes par `tests/Feature/NotificationTest.php`, qui vérifie systématiquement qu'un tiers hors périmètre ne reçoit jamais la notification (`Notification::assertNotSentTo`).

## Observer d'audit

`StageObserver` (enregistré dans `AppServiceProvider::boot()` via `Stage::observe(StageObserver::class)`) :

- `created(Stage $stage)` → crée un `AuditLog` (`action = 'created'`, `modele = Stage::class`, `modele_id = $stage->id`, `utilisateur_id = auth()->id()`).
- `updated(Stage $stage)` → idem avec `action = 'updated'`.

**Portée actuelle** : seul le modèle `Stage` est observé. Aucun autre modèle (`Project`, `Task`, `User`, ...) n'écrit dans `audit_logs` à ce jour.

## Tâche planifiée

`RemindJournalEntry` (commande `journal:remind`) :
- récupère tous les utilisateurs `role = stagiaire` et `is_active = true` ;
- pour chacun, vérifie s'il existe une `JournalEntry` datée du jour ;
- si absente, écrit un message dans les logs applicatifs (`Log::info`) — **aucune notification (mail/base) n'est envoyée**, uniquement une trace log.
- planifiée via `Schedule::command(RemindJournalEntry::class)->dailyAt('18:00')` dans `routes/console.php`.

⚠️ Le planificateur Laravel (`schedule:run`) doit être exécuté périodiquement par un cron externe pour que cette planification soit effective en production (voir [Déploiement](Deploiement)).
