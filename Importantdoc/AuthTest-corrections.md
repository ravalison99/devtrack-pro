# Correction des tests `AuthTest.php`

Date : 2026-08-05

## Contexte

Le fichier [tests/Feature/AuthTest.php](../tests/Feature/AuthTest.php) contient 3 tests qui vérifient le comportement de connexion (login) :

1. Connexion réussie avec des identifiants valides
2. Échec de connexion avec un mauvais mot de passe
3. Échec de connexion pour un utilisateur désactivé (`is_active = false`)

Avant correction, les 3 tests échouaient.

## Erreur rencontrée

```
BadMethodCallException
Call to undefined method App\Models\User::factory()
```

Cette erreur apparaissait dès la première ligne de chaque test qui appelle `User::factory()->create([...])`.

## Cause du problème

Le problème se trouve dans [app/Models/User.php](../app/Models/User.php).

Avant la correction :

```php
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use Notifiable;
    ...
```

Le commentaire PHPDoc `/** @use HasFactory<UserFactory> */` (ligne 13) donne uniquement une indication de typage pour les IDE/analyseurs statiques — **ce n'est pas du code PHP exécutable**. Le trait `HasFactory` n'était donc **jamais réellement importé** dans la classe : seul `use Notifiable;` était présent.

Résultat : la méthode magique `factory()` fournie par le trait `HasFactory` n'existait pas sur le modèle `User`, d'où l'exception `BadMethodCallException`.

C'est une erreur d'inattention assez classique : le docblock `@use` ressemble à une instruction `use` mais n'en a pas l'effet.

## Solution appliquée

Ajout du trait `HasFactory` dans l'instruction `use` de la classe :

```php
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;
    ...
```

Un seul fichier a été modifié : [app/Models/User.php](../app/Models/User.php).

## Vérification

Après correction, exécution de la suite de tests :

```
php artisan test --filter=AuthTest
```

Résultat :

```
PASS  Tests\Feature\AuthTest
✓ un utilisateur peut se connecter avec des identifiants valides
✓ un utilisateur ne peut pas se connecter avec un mauvais mot de passe
✓ un utilisateur desactive ne peut pas se connecter

Tests: 3 passed (11 assertions)
```

L'ensemble de la suite de tests du projet (`php artisan test`) a également été relancé : les 5 tests existants passent, sans régression.

## À retenir

- Un commentaire `/** @use Trait<...> */` seul ne suffit jamais à importer un trait : il faut toujours l'instruction `use Trait;` dans le corps de la classe.
- Quand un modèle Eloquent utilise `Factory` dans les tests (`Model::factory()`), vérifier que le trait `HasFactory` est bien utilisé (`use HasFactory;`) et pas seulement documenté.
