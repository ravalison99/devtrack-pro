# Sécurité

Cette page recense les mesures de sécurité effectivement implémentées dans le code, ainsi que les limites constatées à date.

## Mesures implémentées

### Authentification
- Mots de passe hachés via le cast Eloquent `hashed` sur `User::$casts['password']` (Bcrypt, `BCRYPT_ROUNDS=12` par défaut, `4` en tests) — jamais stockés en clair.
- Vérification du compte actif (`is_active`) à la connexion : un compte désactivé ne peut pas s'authentifier même avec les bons identifiants (`AuthService::authenticate()`).
- Message d'erreur générique (« Identifiants invalides. ») commun aux cas « utilisateur inexistant », « mot de passe erroné » et « compte inactif », pour ne pas laisser un attaquant déduire quels e-mails sont enregistrés.

### Limitation des tentatives de connexion (anti brute-force)
Implémentée dans `AuthController::login()` via `RateLimiter` : 5 échecs autorisés par IP, blocage de 60 secondes au-delà, compteur réinitialisé après une connexion réussie. Voir [Authentification et autorisation](Authentification-et-autorisation#limitation-des-tentatives-de-connexion-brute-force). Testée par `SecurityTest`.

### Autorisation
- Contrôle systématique par **Policies** Laravel (auto-découvertes par convention) à chaque action sensible : création de stage/projet/tâche/utilisateur, consultation/téléchargement de document ou de rapport, changement de statut de tâche. Voir [Authentification et autorisation](Authentification-et-autorisation).
- Les règles d'autorisation empêchent notamment l'**IDOR** (Insecure Direct Object Reference) sur les téléchargements : `DocumentController::download()` et `WeeklyReportController::download()` vérifient explicitement (`can('view', ...)`) que l'utilisateur courant est bien le propriétaire, un admin, ou (pour les rapports) le mentor concerné, avant de servir le fichier — un utilisateur authentifié ne peut donc pas télécharger un document/rapport appartenant à un tiers en devinant son identifiant.

### Traçabilité (audit)
`StageObserver` journalise automatiquement dans `audit_logs` toute création/modification d'un `Stage`, avec l'utilisateur à l'origine de l'action, un horodatage et l'identifiant de l'enregistrement concerné. Testé par `SecurityTest`. **Portée limitée à `Stage`** : aucun autre modèle sensible (`User`, `Task`, `Project`, `Document`) n'est actuellement audité.

### Protection contre l'assignation de masse
Chaque modèle Eloquent définit explicitement `$fillable` (liste blanche), plutôt que `$guarded = []` : seules les colonnes listées peuvent être renseignées via `create()`/`update()` à partir d'un tableau de données externes.

### Protection des attributs sensibles
`User::$hidden = ['password', 'remember_token']` : ces champs sont exclus de toute sérialisation JSON du modèle.

### Validation des entrées
Toutes les routes de création/modification passent par une validation Laravel (`FormRequest::rules()` ou `Request::validate()`) : contraintes de type, de longueur, de format, d'unicité (`unique:users,email`), d'appartenance à un ensemble (`in:...`), d'existence en base (`exists:...`), et de cohérence inter-champs (`different:stagiaire_id`, `after:date_debut`, `after_or_equal:today`).

### Upload de fichiers
Les trois points d'upload (pièces jointes de tâche, documents, futures versions de document) restreignent :
- la **taille** : 5 Mo maximum (`max:5120`) ;
- le **type** : `mimes:pdf,doc,docx,png,jpg,jpeg` uniquement.

Les fichiers sont stockés sur le disque `local` (`storage/app/private`), **hors de la racine publique** (`public/`) et ne sont jamais servis par une URL statique directe : tout accès passe par une route contrôlée authentifiant et autorisant l'utilisateur (voir IDOR ci-dessus).

### CSRF
Protection CSRF standard de Laravel active sur toutes les routes web (middleware par défaut du groupe `web`) ; tous les formulaires Blade incluent `@csrf`, et l'appel `fetch()` JavaScript du tableau Kanban transmet explicitement le jeton via l'en-tête `X-CSRF-TOKEN`.

### Sessions
`SESSION_DRIVER=database` (les sessions ne sont pas stockées côté client), `SESSION_ENCRYPT=false` par défaut (valeur du squelette Laravel, à réévaluer selon le contexte de déploiement).

## Limites et points de vigilance identifiés

- **Middleware de rôle inutilisé** : `EnsureRole` est enregistré mais non appliqué à aucune route ; la sécurité repose entièrement sur les Policies. Ce n'est pas une faille en soi (les Policies couvrent les actions sensibles observées), mais toute nouvelle route ajoutée sans policy associée ne serait protégée par aucun filet de sécurité au niveau routage.
- **`APP_DEBUG=true` par défaut** (`.env.example`) : à désactiver impérativement en production (voir [Déploiement](Deploiement)).
- **Pas de vérification d'e-mail obligatoire** : `email_verified_at` existe en base mais n'est jamais renseigné ni contrôlé dans le flux d'inscription/connexion (il n'y a d'ailleurs pas de flux d'auto-inscription : les comptes sont créés par un admin via `/users`).
- **Pas d'authentification à deux facteurs (2FA)**.
- **Rate limiting basé sur l'IP brute** (`$request->ip()`) sans configuration de proxys de confiance (`TrustProxies`) : derrière un load balancer/reverse proxy non configuré, toutes les requêtes pourraient partager la même IP apparente, avec un risque de blocage collectif ou, à l'inverse, de contournement selon la configuration réseau.
- **Audit incomplet** : seul le cycle de vie de `Stage` est tracé ; les actions sur les utilisateurs, projets, tâches, documents ne le sont pas.
- **Pas de politique de complexité de mot de passe** au-delà de la longueur minimale (8 caractères) — aucune règle Laravel `Password::min(8)->...` (majuscule, chiffre, caractère spécial, vérification contre les fuites connues) n'est appliquée.
- **HTTPS non imposé applicativement** : à la charge de l'infrastructure de déploiement (aucune redirection HTTP→HTTPS ni middleware `secure` dans le code).
