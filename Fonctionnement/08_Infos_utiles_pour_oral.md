# Infos utiles pour l'oral

Ce fichier regroupe des notions qui peuvent aider a repondre aux questions du jury, meme si elles ne sont pas toutes dans le code directement.

## Les types de tests

## Test unitaire

Objectif : tester une petite partie isolee.

Exemple :

- une methode,
- une fonction,
- un comportement precis.

Dans TournaBad :

- `PasswordHashingTest` verifie qu'un mot de passe est bien hashe automatiquement.

Phrase orale :

> Un test unitaire verifie une petite regle isolee. Dans mon projet, j'en ai un sur le hash automatique du mot de passe.

## Test d'integration

Objectif : verifier que plusieurs parties fonctionnent ensemble.

Exemple :

- route + controleur + service + base.

Dans Laravel, les tests `Feature` sont souvent proches de tests d'integration, car ils simulent une requete HTTP et verifient la reponse ou la base.

Dans TournaBad :

- creation d'un tournoi,
- generation d'un round,
- enregistrement d'un score,
- correction de points.

Phrase orale :

> Mes tests Feature verifient des parcours complets de l'application, pas seulement une methode isolee.

## Test fonctionnel

Objectif : verifier une fonctionnalite du point de vue utilisateur ou metier.

Exemple :

- un utilisateur connecte peut generer un round,
- un score met a jour les points,
- on ne peut pas generer un round sans assez de joueurs.

Dans TournaBad, beaucoup de tests Feature sont aussi des tests fonctionnels.

## Test end-to-end

Objectif : simuler un vrai utilisateur dans un navigateur.

Outils possibles :

- Cypress,
- Playwright,
- Selenium.

Exemple end-to-end :

1. ouvrir le site,
2. se connecter,
3. creer un tournoi,
4. ajouter des joueurs,
5. cliquer sur generer un round,
6. verifier que les matchs apparaissent.

Dans TournaBad, tu n'as pas mis en place de vraie suite E2E automatisee. Tu as fait des tests Feature et des verifications manuelles de l'interface.

Phrase orale si on te demande :

> Je n'ai pas automatise de tests end-to-end avec navigateur. J'ai surtout couvert les regles critiques avec des tests Feature Laravel et complete avec des tests manuels de l'interface.

## Pyramide des tests

Une strategie classique :

- beaucoup de tests unitaires,
- plusieurs tests d'integration/fonctionnels,
- moins de tests end-to-end car ils sont plus lents et plus fragiles.

Dans ton projet :

- peu de tests unitaires,
- beaucoup de tests Feature sur les regles importantes,
- pas de tests E2E automatises.

C'est coherent pour un projet Laravel metier ou les regles sont surtout cote serveur.

## SQL utile pour ton projet

Laravel utilise Eloquent, mais il est utile de savoir expliquer des requetes SQL.

## SELECT simple

Recuperer tous les tournois :

```sql
SELECT *
FROM tournaments;
```

Recuperer les tournois d'un utilisateur :

```sql
SELECT *
FROM tournaments
WHERE creator_id = 1;
```

Equivalent Eloquent :

```php
Tournament::where('creator_id', $user->id)->get();
```

## SELECT avec tri

Recuperer les derniers tournois :

```sql
SELECT *
FROM tournaments
WHERE creator_id = 1
ORDER BY starts_on DESC
LIMIT 5;
```

Equivalent Eloquent :

```php
Tournament::where('creator_id', $user->id)
    ->latest('starts_on')
    ->take(5)
    ->get();
```

## SELECT avec JOIN

Recuperer les joueurs d'un tournoi :

```sql
SELECT players.*
FROM players
JOIN tournaments ON tournaments.id = players.tournament_id
WHERE tournaments.id = 3;
```

Equivalent Eloquent :

```php
$tournament->players()->get();
```

## Compter les joueurs d'un tournoi

```sql
SELECT COUNT(*) AS players_count
FROM players
WHERE tournament_id = 3
  AND deleted_at IS NULL;
```

Equivalent Eloquent :

```php
$tournament->players()->count();
```

## Recuperer les matchs d'un round

```sql
SELECT *
FROM tournament_matches
WHERE round_id = 10
ORDER BY court_number ASC;
```

Equivalent Eloquent :

```php
$round->matches()->orderBy('court_number')->get();
```

## Recuperer les joueurs d'un match

Comme les joueurs et matchs sont en relation plusieurs-a-plusieurs, on passe par `match_players`.

```sql
SELECT players.first_name, match_players.team_number
FROM players
JOIN match_players ON match_players.player_id = players.id
WHERE match_players.tournament_match_id = 5
ORDER BY match_players.team_number;
```

Equivalent Eloquent :

```php
$match->players;
```

## Recuperer les joueurs en attente d'un round

```sql
SELECT players.first_name
FROM players
JOIN round_waiting_players ON round_waiting_players.player_id = players.id
WHERE round_waiting_players.round_id = 10;
```

## Calculer le total des points d'un tournoi

```sql
SELECT SUM(points) AS total_points
FROM players
WHERE tournament_id = 3;
```

## Exemple de requete de classement

```sql
SELECT first_name, points
FROM players
WHERE tournament_id = 3
  AND deleted_at IS NULL
ORDER BY points DESC, first_name ASC;
```

Equivalent Eloquent simplifie :

```php
$tournament->players()
    ->orderByDesc('points')
    ->orderBy('first_name')
    ->get();
```

## INSERT

Ajouter un joueur :

```sql
INSERT INTO players (tournament_id, first_name, is_active, points, created_by, created_at, updated_at)
VALUES (3, 'Alice', 1, 0, 1, NOW(), NOW());
```

Equivalent Eloquent :

```php
Player::create([
    'tournament_id' => $tournament->id,
    'first_name' => 'Alice',
    'is_active' => true,
    'points' => 0,
    'created_by' => $user->id,
]);
```

## UPDATE

Modifier les points d'un joueur :

```sql
UPDATE players
SET points = 15
WHERE id = 8;
```

Equivalent Eloquent :

```php
$player->update(['points' => 15]);
```

## DELETE et soft delete

Suppression classique :

```sql
DELETE FROM players
WHERE id = 8;
```

Mais dans TournaBad, les joueurs utilisent un soft delete. Laravel met plutot `deleted_at` :

```sql
UPDATE players
SET deleted_at = NOW()
WHERE id = 8;
```

Cela garde l'historique.

## Methode projet

## Agile

Agile est une maniere de travailler par iterations.

Au lieu de tout figer au debut, on avance par petites etapes :

1. comprendre le besoin,
2. developper une partie,
3. tester,
4. ajuster,
5. continuer.

TournaBad correspond plutot a une demarche agile legere, car le besoin s'est precise au fil des essais.

## Scrum

Scrum est une methode agile structuree.

Elle contient :

- des sprints,
- un Product Owner,
- un Scrum Master,
- une equipe de developpement,
- des daily meetings,
- des revues,
- des retrospectives.

Dans ton projet, tu n'as pas vraiment applique Scrum, car tu etais principalement seul.

Phrase orale :

> Je n'ai pas applique Scrum complet, car le projet etait individuel. J'ai plutot utilise une organisation proche de Kanban, avec des taches priorisees et une progression iterative.

## Kanban

Kanban organise le travail sous forme de flux.

Colonnes possibles :

- a faire,
- en cours,
- a tester,
- termine.

Avantages :

- simple,
- adapte a un projet seul,
- permet de prioriser,
- accepte les changements.

Dans TournaBad :

- priorite aux tournois et joueurs,
- ensuite generation des rounds,
- ensuite scores, timer, classements,
- enfin securite, tests, deploiement.

## Backlog

Le backlog est la liste des fonctionnalites ou taches a realiser.

Exemples de backlog TournaBad :

- creer un compte,
- creer un tournoi,
- ajouter des joueurs,
- generer un round,
- enregistrer un score,
- afficher un classement,
- deployer sur VPS.

## Priorisation

Prioriser signifie choisir ce qui doit etre fait en premier.

Dans TournaBad, les fonctionnalites indispensables sont venues avant les ameliorations visuelles.

Exemple :

1. creer tournoi/joueurs,
2. generer rounds,
3. enregistrer scores,
4. afficher classement,
5. ajouter timer et options,
6. ameliorer interface.

## MCD, MPD et conception

## MCD

Le MCD decrit les donnees avec un vocabulaire metier.

Dans TournaBad :

- utilisateur,
- tournoi,
- joueur,
- round,
- match,
- score.

Objectif :

- comprendre les relations avant la base technique.

## MPD

Le MPD traduit le MCD en structure technique.

On y retrouve :

- noms de tables,
- colonnes,
- types,
- cles primaires,
- cles etrangeres,
- contraintes.

Exemple :

- `players.tournament_id` relie un joueur a un tournoi.

## UML dans ton diapo

## Cas d'utilisation

Montre ce que les acteurs peuvent faire.

Exemple :

- un visiteur peut s'inscrire,
- un utilisateur peut creer un tournoi,
- un utilisateur peut ajouter des joueurs,
- un utilisateur peut generer un round.

## Activite

Montre le deroulement d'une action.

Exemple pour generer un round :

1. ouvrir le tournoi,
2. verifier les joueurs,
3. calculer les matchs,
4. enregistrer,
5. afficher le resultat.

## Sequence

Montre les echanges dans le temps.

Exemple :

1. vue,
2. controleur,
3. service,
4. modeles/base,
5. retour vers la vue.

## Architecture de production

## Local

En local, tu utilises :

- Windows,
- VS Code,
- WAMP,
- PHP,
- MySQL/phpMyAdmin,
- Composer,
- NPM/Vite.

## Production

En production :

- VPS Debian,
- Nginx,
- PHP-FPM,
- MariaDB,
- GitHub pour recuperer le code,
- Composer/NPM pour installer et construire.

## Pourquoi le dossier `public` est important

Laravel doit exposer uniquement `public`.

Si le serveur expose toute la racine du projet, il pourrait exposer :

- `.env`,
- code source,
- fichiers internes,
- dependances.

Donc Nginx doit pointer vers :

```text
/chemin/du/projet/public
```

## Les commandes utiles

Installer les dependances PHP :

```bash
composer install
```

Installer les dependances front :

```bash
npm install
```

Compiler le front :

```bash
npm run build
```

Lancer les migrations :

```bash
php artisan migrate
```

Lancer les tests :

```bash
php artisan test
```

Vider le cache de configuration :

```bash
php artisan config:clear
```

Generer une cle Laravel :

```bash
php artisan key:generate
```

## Questions pieges possibles

### Est-ce que ton projet est full agile ?

Non. Il est plutot en demarche agile legere / Kanban. Scrum complet n'etait pas adapte car tu etais seul.

### Est-ce que tu as des tests end-to-end ?

Non, pas automatises. Tu as des tests unitaires et Feature Laravel, plus des verifications manuelles de l'interface.

### Pourquoi utiliser Eloquent plutot que SQL ?

Eloquent rend le code plus lisible, gere les relations, limite les injections SQL via les requetes parametrees et s'integre bien avec Laravel.

### Pourquoi garder des migrations ?

Les migrations versionnent la structure de la base. Elles permettent de reconstruire la base de maniere reproductible.

### Pourquoi des transactions ?

Pour garantir la coherence quand une action modifie plusieurs tables.

### Quelle difference entre authentification et autorisation ?

Authentification : savoir qui est connecte.

Autorisation : savoir si cette personne a le droit de faire l'action.

Dans TournaBad :

- `auth` verifie la connexion,
- `creator_id` verifie que le tournoi appartient a l'utilisateur.

### Quelle difference entre validation front et back ?

Validation front : aide l'utilisateur dans le navigateur.

Validation back : protege vraiment l'application.

Le back est obligatoire parce qu'un utilisateur peut modifier ou contourner le HTML.

## Mini scenario complet a expliquer au jury

Exemple : generation d'un round.

1. L'utilisateur clique sur "generer le prochain tour".
2. JavaScript intercepte le formulaire et envoie une requete POST.
3. Laravel verifie le token CSRF.
4. La route appelle `TournamentController@generateRound`.
5. Le middleware `auth` garantit que l'utilisateur est connecte.
6. Le controleur verifie que le tournoi appartient a l'utilisateur.
7. Le controleur verifie qu'il y a assez de joueurs ou d'equipes.
8. Il appelle `RoundGenerator`.
9. Le service ouvre une transaction et verrouille le tournoi.
10. Il choisit les joueurs/equipes, forme les matchs, enregistre les attentes.
11. Il retourne un payload JSON.
12. JavaScript affiche ou redirige vers le round genere.

Phrase orale :

> Cet exemple montre tout le cycle de l'application : route, controleur, verification des droits, service metier, modeles Eloquent, base de donnees, puis retour vers l'interface.
