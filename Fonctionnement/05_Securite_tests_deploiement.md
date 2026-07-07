# Securite, tests et deploiement

## Securite applicative

## Authentification

Les routes sensibles sont dans le middleware `auth`.

Cela veut dire qu'un visiteur non connecte ne peut pas acceder :

- au dashboard,
- aux tournois,
- aux joueurs,
- aux scores,
- aux equipes,
- aux parametres.

Les routes de connexion et inscription sont dans le middleware `guest`, donc elles sont reservees aux utilisateurs non connectes.

## Verification du proprietaire

L'authentification seule ne suffit pas.

Un utilisateur connecte pourrait essayer de changer l'id dans l'URL, par exemple :

`/tournaments/3`

Pour eviter cela, `TournamentController` utilise une methode `userTournament`.

Elle compare :

- l'id de l'utilisateur connecte,
- `creator_id` du tournoi.

Si ce n'est pas le bon proprietaire, Laravel renvoie une erreur 403.

Le controleur verifie aussi les ressources liees :

- un match doit appartenir au tournoi demande,
- un joueur doit appartenir au tournoi demande,
- une equipe doit appartenir au tournoi demande,
- un round doit appartenir au tournoi demande.

## Validation serveur

Les formulaires sont valides dans les controleurs avec `$request->validate`.

Exemples :

- email obligatoire et valide,
- mot de passe de 8 caracteres minimum,
- nombre de terrains entre 1 et 20,
- duree du round superieure a zero,
- scores entiers positifs,
- format autorise,
- image de profil limitee a 2 Mo.

La validation serveur est importante parce qu'on ne peut jamais faire confiance uniquement au navigateur.

## CSRF

Tous les formulaires Blade utilisent `@csrf`.

Les requetes AJAX ajoutent le token dans l'en-tete :

`X-CSRF-TOKEN`

Le token est disponible dans le layout :

`<meta name="csrf-token" content="{{ csrf_token() }}">`

Cela protege contre les formulaires envoyes depuis un autre site.

## XSS

Blade echappe automatiquement les variables affichees avec `{{ ... }}`.

Cote JavaScript, les donnees injectees dans du HTML passent par `TournamentUtils.escapeHtml`.

Cela evite qu'un nom de joueur ou une valeur affichee puisse devenir du code HTML/JavaScript executable.

## Injection SQL

Le projet utilise principalement Eloquent et le Query Builder.

Les valeurs utilisateur ne sont pas concatenees directement dans des requetes SQL.

Les quelques `selectRaw` sont utilises pour des agregations fixes, sans inserer de saisie utilisateur brute.

## Mots de passe

Le modele `User` utilise :

`'password' => 'hashed'`

Donc Laravel hashe automatiquement le mot de passe lors de l'affectation.

Les mots de passe ne sont pas stockes en clair.

## Sessions et cookies

Apres connexion ou inscription, la session est regeneree.

But : limiter le risque de fixation de session.

Lors de la deconnexion :

- `Auth::logout()`,
- invalidation de session,
- regeneration du token CSRF.

Dans `config/session.php` :

- `http_only` est active par defaut,
- `same_site` vaut `lax` par defaut,
- la duree de session est configurable.

## Donnees personnelles

Les donnees collectees sont limitees :

- compte : nom, email, mot de passe hashe, photo optionnelle,
- joueur : prenom principalement,
- nom/email joueur dans la migration existent mais ne sont pas utilises fortement dans l'interface.

Les secrets techniques sont dans `.env`, qui ne doit pas etre versionne.

## Coherence des donnees

## Contraintes de base

La base contient des cles etrangeres :

- un joueur appartient a un tournoi,
- un round appartient a un tournoi,
- un match appartient a un round et un tournoi,
- un score appartient a un match,
- un joueur en attente appartient a un round.

Les suppressions en cascade evitent les donnees orphelines.

## Contraintes uniques

Exemples :

- un round_number unique par tournoi,
- un court_number unique par round,
- un joueur unique dans un meme match,
- un score unique par match,
- un joueur unique dans les attentes d'un round.

Ces contraintes securisent la coherence meme si une erreur applicative se produit.

## Transactions

Les traitements sensibles sont dans des transactions :

- generation d'un round,
- enregistrement/correction d'un score,
- suppression d'un round,
- reset d'un tournoi,
- creation/modification/suppression d'equipes.

Une transaction garantit que tout est enregistre ensemble ou rien n'est conserve.

Exemple : si la generation cree le round mais echoue pendant les matchs, la transaction annule tout.

## Tests automatises

Les tests sont dans `tests/Feature` et `tests/Unit`.

Ils utilisent souvent `RefreshDatabase`, donc chaque scenario repart d'une base propre.

## Tests unitaires

Exemple :

- `PasswordHashingTest` verifie que le mot de passe est hashe automatiquement.

## Tests feature

Ils traversent plusieurs couches :

- route,
- controleur,
- service,
- modeles,
- base de donnees.

Scenarios couverts :

- consultation du dashboard,
- affichage admin,
- mise a jour du profil,
- creation de tournoi,
- limite de 5 tournois,
- creation de tournoi par equipes aleatoires,
- creation de tournoi par equipes predefinies,
- ajout/modification d'equipes,
- generation de rounds,
- minimum de joueurs/equipes,
- rotation des partenaires,
- cas 26 joueurs / 6 terrains,
- score et mise a jour des points,
- correction/suppression de round avec rollback des points,
- points manuels.

## Pourquoi ces tests sont utiles

Ils prouvent que les regles sensibles fonctionnent :

- on ne genere pas un round impossible,
- les repetitions sont limitees,
- les equipes predefinies restent coherentes,
- un score corrige ne double pas les points,
- supprimer un round retire l'impact des scores.

## Deploiement

L'application Laravel est prevue pour etre deployee sur un serveur web.

Architecture de production :

1. Le navigateur envoie une requete.
2. Nginx la recoit.
3. Nginx sert les fichiers statiques ou transmet PHP a PHP-FPM.
4. Laravel traite la requete.
5. Laravel communique avec MariaDB.
6. La reponse revient au navigateur.

Point important : le serveur web doit pointer vers le dossier `public`.

Cela evite d'exposer :

- `.env`,
- le code source,
- `vendor`,
- `storage`,
- les fichiers internes.

Etapes classiques :

- recuperer le code depuis GitHub,
- installer Composer,
- installer NPM,
- compiler les assets avec Vite,
- creer le `.env` de production,
- lancer les migrations,
- configurer Nginx,
- donner les droits a `storage` et `bootstrap/cache`.

## CI GitHub Actions

Le projet utilise un workflow GitHub Actions.

Objectif :

- installer les dependances,
- construire les ressources,
- lancer les tests,
- detecter les erreurs avant de deployer.

Le deploiement n'est pas encore totalement automatique, mais la CI pose une base solide.
