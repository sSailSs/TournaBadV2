# Lexique technique TournaBad

Ce fichier sert a reconnaitre les mots techniques et a pouvoir les expliquer simplement a l'oral.

## Laravel

Laravel est un framework PHP. Un framework donne une structure et des outils prets a l'emploi pour creer une application web.

Dans TournaBad, Laravel sert a gerer :

- les routes,
- les controleurs,
- les vues Blade,
- les modeles Eloquent,
- l'authentification,
- les sessions,
- la validation,
- les migrations,
- les tests.

Phrase orale :

> Laravel me fournit la structure de l'application. Je l'utilise pour organiser le code en MVC, gerer les routes, les vues, la base de donnees, l'authentification et la validation.

## PHP

PHP est le langage serveur utilise par Laravel. Il s'execute cote serveur, pas dans le navigateur.

Dans TournaBad, PHP execute :

- les controleurs,
- les modeles,
- les services,
- les validations,
- les acces base.

## Framework

Un framework est une base de travail qui impose une organisation et fournit des composants reutilisables.

Laravel evite de tout coder a la main : routes, sessions, validation, securite, ORM, tests.

## MVC

MVC signifie Modele, Vue, Controleur.

- Modele : represente les donnees et les relations avec la base.
- Vue : affiche l'interface utilisateur.
- Controleur : recoit la requete, coordonne le traitement et renvoie une reponse.

Dans TournaBad :

- Modeles : `Tournament`, `Player`, `Round`, `TournamentMatch`.
- Vues : fichiers Blade dans `resources/views`.
- Controleurs : `AuthController`, `HomeController`, `TournamentController`.

## Route

Une route relie une URL et une methode HTTP a une action du code.

Exemple :

```php
Route::post('/tournaments/{tournament}/rounds/generate', [TournamentController::class, 'generateRound']);
```

Cela signifie : quand l'utilisateur envoie une requete POST sur cette URL, Laravel appelle `generateRound`.

## Controleur

Un controleur recoit les requetes et prepare les reponses.

Il peut :

- valider les donnees,
- verifier les droits,
- appeler un service,
- charger des modeles,
- renvoyer une vue, une redirection ou du JSON.

## Modele

Un modele represente une table de la base.

Exemple :

- `Tournament` represente la table `tournaments`.
- `Player` represente la table `players`.

Avec Eloquent, on manipule les lignes comme des objets PHP.

## Vue

Une vue est le fichier qui genere le HTML affiche dans le navigateur.

Laravel utilise Blade :

- `{{ $variable }}` affiche une variable en l'echappant,
- `@csrf` ajoute un token de protection,
- `@auth` affiche un bloc si l'utilisateur est connecte,
- `@foreach` boucle sur des donnees.

## Blade

Blade est le moteur de templates de Laravel.

Il permet d'ecrire du HTML avec des instructions Laravel simples.

Exemple :

```blade
<h1>{{ $tournament->name }}</h1>
```

Blade echappe automatiquement la variable pour limiter les risques XSS.

## Eloquent

Eloquent est l'ORM de Laravel.

Un ORM permet de manipuler la base de donnees avec des objets au lieu d'ecrire du SQL partout.

Exemple :

```php
$tournament->players()->where('is_active', true)->get();
```

Cela recupere les joueurs actifs d'un tournoi.

## ORM

ORM signifie Object Relational Mapping.

Il fait le lien entre :

- les objets PHP,
- les tables SQL.

Avantage : code plus lisible et requetes parametrees automatiquement.

## Migration

Une migration est un fichier qui cree ou modifie une table.

Elle permet de versionner la structure de la base.

Exemple :

- creation de `players`,
- ajout de `round_duration_seconds`,
- creation de `match_scores`.

Commande typique :

```bash
php artisan migrate
```

## Seeder

Un seeder sert a remplir la base avec des donnees de depart ou de test.

Dans ton projet, le seeder existe mais le coeur de la demonstration repose surtout sur les migrations et les tests.

## Factory

Une factory sert a creer rapidement de fausses donnees dans les tests.

Exemple : `User::factory()->create()` cree un utilisateur pour un test.

## Middleware

Un middleware est une couche intermediaire qui agit avant ou apres une requete.

Dans TournaBad :

- `auth` verifie que l'utilisateur est connecte,
- `guest` reserve certaines pages aux visiteurs non connectes.

## Authentification

L'authentification sert a savoir qui est l'utilisateur.

Dans TournaBad :

- connexion par email/mot de passe,
- inscription,
- session utilisateur,
- deconnexion.

## Autorisation

L'autorisation sert a savoir si un utilisateur a le droit de faire une action.

Exemple : un utilisateur connecte ne doit pas pouvoir modifier le tournoi d'un autre.

Dans TournaBad, cela est verifie avec `creator_id`.

## Session

Une session permet de garder l'utilisateur connecte entre plusieurs pages.

Laravel stocke un identifiant de session dans un cookie et les donnees de session cote serveur.

Dans TournaBad :

- la session est regeneree apres connexion,
- elle est invalidee a la deconnexion.

## Cookie

Un cookie est une petite donnee stockee par le navigateur.

Dans Laravel, le cookie de session permet de retrouver la session de l'utilisateur.

Option importante :

- `http_only` empeche JavaScript de lire le cookie.

## CSRF

CSRF signifie Cross-Site Request Forgery.

C'est une attaque ou un site externe essaie de faire envoyer une requete a ton application a la place de l'utilisateur.

Exemple :

- l'utilisateur est connecte a TournaBad,
- il visite un site malveillant,
- ce site essaie d'envoyer un formulaire de suppression de tournoi.

Protection :

- Laravel ajoute un token CSRF dans les formulaires avec `@csrf`.
- Les requetes AJAX envoient aussi `X-CSRF-TOKEN`.
- Si le token est absent ou faux, Laravel bloque.

Phrase orale :

> Le CSRF consiste a faire envoyer une action par un utilisateur connecte sans son accord. Laravel protege les formulaires avec un token CSRF.

## XSS

XSS signifie Cross-Site Scripting.

C'est une attaque ou un utilisateur injecte du HTML ou JavaScript dans une page.

Exemple :

Un joueur s'appelle :

```html
<script>alert('attaque')</script>
```

Si l'application affiche ce nom sans protection, le script peut s'executer.

Protections dans TournaBad :

- Blade echappe les variables avec `{{ }}`,
- le JavaScript utilise `escapeHtml`.

Phrase orale :

> Le XSS consiste a injecter du script dans une page. Je limite ce risque avec l'echappement automatique de Blade et une fonction d'echappement cote JavaScript.

## Injection SQL

Une injection SQL consiste a modifier une requete SQL en injectant du texte malveillant.

Exemple dangereux :

```sql
SELECT * FROM users WHERE email = '$email'
```

Si `$email` contient du SQL, la requete peut etre detournee.

Dans TournaBad :

- Eloquent et le Query Builder utilisent des requetes parametrees,
- les entrees sont validees,
- il n'y a pas de concatenation SQL directe avec des saisies utilisateur.

## Hash

Un hash transforme une valeur en une empreinte non lisible.

Pour les mots de passe, on ne stocke pas le mot de passe en clair. On stocke son hash.

Dans TournaBad :

```php
'password' => 'hashed'
```

Laravel hashe automatiquement le mot de passe.

## Validation

La validation verifie que les donnees recues respectent les regles.

Exemples :

- email valide,
- mot de passe minimum 8 caracteres,
- score entier positif,
- nombre de terrains entre 1 et 20.

La validation cote serveur est obligatoire parce qu'un utilisateur peut contourner le HTML.

## Requete HTTP

Une requete HTTP est un message envoye par le navigateur au serveur.

Exemples :

- ouvrir une page,
- envoyer un formulaire,
- enregistrer un score.

## Methodes HTTP

- `GET` : lire/afficher une ressource.
- `POST` : creer ou declencher une action.
- `PATCH` : modifier partiellement.
- `DELETE` : supprimer.

Dans TournaBad :

- `GET /tournaments` liste les tournois,
- `POST /tournaments` cree un tournoi,
- `PATCH /dashboard/profile` modifie le profil,
- `DELETE /tournaments/{tournament}` supprime un tournoi.

## JSON

JSON est un format de donnees utilise entre serveur et navigateur.

Exemple :

```json
{
  "message": "Tour genere avec succes",
  "round": {
    "id": 1,
    "round_number": 1
  }
}
```

Dans TournaBad, les generations de rounds et scores peuvent renvoyer du JSON.

## AJAX / fetch

AJAX signifie qu'une page peut envoyer une requete au serveur sans recharger toute la page.

Dans ton code, JavaScript utilise `fetch`.

Exemple :

- envoyer le formulaire de generation de round,
- enregistrer un score,
- recevoir la reponse JSON.

## API

Une API est une interface permettant a deux parties du logiciel de communiquer.

TournaBad n'est pas une API publique, mais certaines routes renvoient du JSON pour le JavaScript.

## CRUD

CRUD signifie :

- Create : creer,
- Read : lire,
- Update : modifier,
- Delete : supprimer.

Exemple tournoi :

- creer un tournoi,
- afficher ses details,
- modifier ses parametres,
- supprimer le tournoi.

## Cle primaire

Une cle primaire identifie une ligne de facon unique.

Exemple :

- `id` dans `tournaments`.

## Cle etrangere

Une cle etrangere relie une table a une autre.

Exemple :

- `players.tournament_id` pointe vers `tournaments.id`.

## Contrainte unique

Une contrainte unique empeche les doublons.

Exemple :

- un match ne peut avoir qu'un score,
- un round ne peut pas avoir deux fois le meme terrain.

## Transaction

Une transaction regroupe plusieurs operations SQL.

Soit tout reussit, soit tout est annule.

Dans TournaBad, c'est utilise pour :

- generation d'un round,
- score,
- suppression d'un round,
- reset,
- equipes.

## Commit

Dans une transaction SQL, le commit valide les changements.

## Rollback

Le rollback annule les changements d'une transaction en cas d'erreur.

## Soft delete

Un soft delete ne supprime pas vraiment la ligne. Il remplit une colonne `deleted_at`.

Dans TournaBad, les joueurs utilisent `SoftDeletes`, ce qui permet de garder l'historique des matchs.

## CI

CI signifie Integration Continue.

Objectif : lancer automatiquement des controles quand le code est pousse sur GitHub.

Dans TournaBad, GitHub Actions peut installer les dependances, construire le front et lancer les tests.

## CD

CD signifie Deploiement Continu.

Objectif : deployer automatiquement apres les tests.

Dans ton projet, le deploiement reste semi-automatise.

## VPS

Un VPS est un serveur virtuel loue.

Dans ton projet, il sert a heberger l'application Laravel.

## Nginx

Nginx est le serveur web.

Il recoit les requetes HTTP/HTTPS et les transmet a PHP-FPM pour les fichiers PHP.

## PHP-FPM

PHP-FPM execute le code PHP en production.

Nginx ne lance pas Laravel directement : il transmet les requetes PHP a PHP-FPM.

## MySQL / MariaDB

Base de donnees relationnelle utilisee pour stocker les donnees persistantes.

MariaDB est compatible avec MySQL.

## SQLite

Base de donnees legere souvent utilisee pour les tests.

Dans Laravel, elle permet d'executer les tests rapidement avec une base isolee.

## Composer

Gestionnaire de dependances PHP.

Exemple :

```bash
composer install
```

## NPM

Gestionnaire de dependances JavaScript.

Exemple :

```bash
npm install
```

## Vite

Outil de build front-end.

Il sert a compiler et optimiser les ressources front.

Commandes :

```bash
npm run dev
npm run build
```

## PHPUnit

Outil de tests PHP utilise par Laravel.

Commande :

```bash
php artisan test
```

## Test unitaire

Test qui verifie une petite partie isolee du code.

Exemple dans TournaBad :

- verifier que le mot de passe est hashe.

## Test d'integration / Feature test

Test qui traverse plusieurs couches de l'application.

Dans Laravel, les Feature tests verifient souvent :

- route,
- controleur,
- service,
- base de donnees,
- reponse HTTP.

Exemple :

- generer un round via une requete POST et verifier les lignes creees.

## Test end-to-end

Test qui simule un utilisateur dans un vrai navigateur.

Exemple :

- ouvrir le site,
- se connecter,
- creer un tournoi,
- cliquer sur generer un round,
- verifier l'affichage.

Dans TournaBad, il y a surtout des tests unitaires et Feature. Il n'y a pas de vraie suite end-to-end automatisee type Playwright/Cypress.

## Kanban

Kanban est une methode visuelle de gestion de projet.

Elle organise les taches en colonnes :

- a faire,
- en cours,
- termine.

Elle convient bien a un projet individuel avec des besoins qui evoluent.

## Scrum

Scrum est une methode agile avec des roles et ceremonies :

- Product Owner,
- Scrum Master,
- equipe,
- sprint,
- daily,
- review,
- retrospective.

Dans ton projet, tu n'as pas applique Scrum complet, car tu etais principalement seul.

## Agile

Agile est une approche iterative : on avance par petites etapes, on teste, on ajuste.

TournaBad se rapproche d'une demarche agile legere : observation, backlog, priorisation, developpement progressif, tests.

## MCD

Modele Conceptuel de Donnees.

Il decrit les entites metier et leurs relations sans se concentrer sur la technique SQL.

## MPD

Modele Physique de Donnees.

Il traduit le modele en tables, colonnes, cles primaires et cles etrangeres.

## Diagramme de cas d'utilisation

Diagramme UML qui montre ce que les acteurs peuvent faire dans l'application.

Dans TournaBad :

- visiteur : inscription/connexion,
- utilisateur : tournois, joueurs, rounds, scores.

## Diagramme d'activite

Diagramme qui montre le deroulement logique d'une action.

Exemple :

- generer un round,
- verifier les joueurs,
- former les matchs,
- enregistrer.

## Diagramme de sequence

Diagramme qui montre les echanges dans le temps entre plusieurs composants.

Exemple :

- vue,
- controleur,
- service,
- modeles,
- base.
