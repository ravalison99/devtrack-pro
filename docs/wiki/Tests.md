# Tests

## Outillage

- Framework : **PHPUnit ^11.5** (`composer.json`), configuration dans `phpunit.xml`.
- Les tests s'exécutent avec une base **SQLite en mémoire** (`DB_DATABASE=:memory:`), un mailer `array`, une file d'attente `sync` (exécution immédiate, sans worker) et un cache `array` — configuration isolée de l'environnement de développement (voir bloc `<php>` de `phpunit.xml`).
- `BCRYPT_ROUNDS=4` en test (au lieu de 12 par défaut) pour accélérer le hachage des mots de passe.

## Lancer la suite de tests

```bash
composer run test
# équivalent à :
php artisan config:clear --ansi
php artisan test
```

Ou directement :

```bash
php artisan test
# ou
vendor/bin/phpunit
```

Pour lancer un seul fichier :

```bash
php artisan test tests/Feature/SecurityTest.php
```

## Organisation

```
tests/
├── TestCase.php
├── Unit/
│   └── ExampleTest.php
└── Feature/
    ├── AuthTest.php
    ├── AutorisationMatriceTest.php
    ├── DashboardTest.php
    ├── DocumentTest.php
    ├── ExampleTest.php
    ├── JournalTest.php
    ├── KanbanTest.php
    ├── NotificationTest.php
    ├── ProjectTest.php
    ├── SecurityTest.php
    ├── StageTest.php
    ├── TaskTest.php
    ├── UserManagementTest.php
    └── WeeklyReportTest.php
```

La quasi-totalité de la couverture se trouve dans `tests/Feature` (tests d'intégration HTTP, via `RefreshDatabase` + `actingAs()`), suivant un découpage **un fichier de test par module fonctionnel**. `tests/Unit` ne contient que le test d'exemple généré par défaut par Laravel — il n'y a pas de tests unitaires isolés des services/repositories à ce jour.

## Exemples de scénarios couverts

- **`SecurityTest`** : traçabilité de la création d'un `Stage` dans `audit_logs` (via `StageObserver`) ; blocage de la 6ᵉ tentative de connexion échouée ; réinitialisation du compteur de tentatives après une connexion réussie.
- **`UserManagementTest`** : création d'un utilisateur de chaque rôle par un admin ; hachage effectif du mot de passe stocké ; refus (403) pour un mentor ou un stagiaire tentant de créer un utilisateur ; refus d'un e-mail déjà utilisé ; accès à la liste des utilisateurs réservé à l'admin ; tri (nom croissant/décroissant) ; pagination à 5/page ; recherche classique et recherche AJAX (fragment HTML retourné) ; modification d'un utilisateur ; désactivation par un admin ; auto-désactivation refusée ; désactivation refusée à un mentor.
- **`WeeklyReportTest`** : soumission valide, génération du PDF, téléchargement scopé au mentor du stagiaire, et **refus explicite** d'une deuxième soumission pour la même semaine (`assertSessionHasErrors('semaine')`).
- **`NotificationTest`** : couvre en plus la suppression d'une notification par son propriétaire et le refus (403) pour un tiers qui tenterait de supprimer la notification d'un autre utilisateur.
- **`AutorisationMatriceTest`** : audit transversal de la matrice des privilèges — accès direct par identifiant (URL manipulée) refusé pour toute ressource hors périmètre (tâche, Kanban), redirection d'un visiteur non authentifié, pages réservées à l'admin inaccessibles aux autres rôles. Complète les vérifications de périmètre déjà présentes dans `TaskTest`, `ProjectTest` et `StageTest` (scoping des listes par rôle).
- Les autres fichiers (`TaskTest`, `KanbanTest`, `StageTest`, `ProjectTest`, `JournalTest`, `WeeklyReportTest`, `DocumentTest`, `NotificationTest`, `DashboardTest`, `AuthTest`) couvrent respectivement les règles métier et d'autorisation propres à chaque module décrit dans [Modules fonctionnels](Modules-fonctionnels).

## Conventions observées

- Noms de méthodes de test en **français, snake_case descriptif** (ex. `test_apres_cinq_echecs_de_connexion_la_sixieme_tentative_est_bloquee`), lisibles comme des spécifications.
- Utilisation systématique de `User::factory()->create(['role' => '...'])` pour construire le contexte d'autorisation d'un scénario.
- Assertions orientées comportement (`assertRedirect`, `assertForbidden`, `assertDatabaseHas`/`Missing`/`Count`, `assertSessionHasErrors`) plutôt que sur les détails d'implémentation.
