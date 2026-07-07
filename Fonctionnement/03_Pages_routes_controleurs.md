# Pages, routes et controleurs

## Routes publiques

Dans `routes/web.php` :

- `GET /` -> `HomeController@index`
- `GET /mentions-legales` -> `HomeController@legalNotices`
- `GET /confidentialite` -> `HomeController@privacyPolicy`

Si l'utilisateur n'est pas connecte, l'accueil affiche `home.blade.php`.
S'il est connecte, l'accueil affiche `start.blade.php` avec ses statistiques et ses derniers tournois.

## Authentification

Routes dans le groupe `guest` :

- `GET /login` : affiche le formulaire de connexion.
- `POST /login` : valide les identifiants et connecte l'utilisateur.
- `GET /register` : affiche le formulaire d'inscription.
- `POST /register` : cree le compte et connecte l'utilisateur.

Routes dans le groupe `auth` :

- `POST /logout` : deconnecte l'utilisateur.

`AuthController` fait trois choses importantes :

- validation des champs,
- utilisation de `Auth::attempt` ou `Auth::login`,
- regeneration de session apres connexion/inscription.

La deconnexion invalide la session et regenere le token CSRF.

## Compte utilisateur

Routes connectees :

- `GET /dashboard`
- `GET /dashboard/tournaments`
- `PATCH /dashboard/profile`
- `PATCH /dashboard/password`

`HomeController` gere :

- les statistiques du compte,
- l'affichage de certains elements admin si `is_admin` vaut true,
- la mise a jour du profil,
- l'upload de photo de profil,
- le changement de mot de passe.

La validation protege les donnees : nom obligatoire, email valide et unique, image limitee a 2 Mo, mot de passe courant obligatoire pour changer le mot de passe.

## Liste et creation des tournois

Routes :

- `GET /tournaments` -> liste les tournois de l'utilisateur.
- `GET /tournaments/create` -> formulaire de creation.
- `POST /tournaments` -> cree le tournoi.
- `DELETE /tournaments/{tournament}` -> supprime le tournoi.

Dans `TournamentController@store`, l'application :

1. verifie que l'utilisateur n'a pas deja 5 tournois,
2. valide le nom, la date, le nombre de terrains, la duree et la description,
3. detecte le type de creation : double ou equipe,
4. configure les options d'equipes si besoin,
5. calcule la duree totale en secondes,
6. cree la ligne en base,
7. redirige vers la bonne page.

Si le tournoi est en equipes predefinies, l'utilisateur est envoye vers la page des equipes. Si les equipes sont aleatoires, il est envoye vers les joueurs.

## Page principale d'un tournoi

Route :

- `GET /tournaments/{tournament}` -> `TournamentController@show`

Cette page affiche :

- le tournoi,
- le round courant ou selectionne,
- les matchs,
- les joueurs/equipes en attente,
- le timer,
- les sons,
- le mini-classement,
- les defis/statistiques rapides,
- les liens vers parametres, joueurs, equipes, points, final.

Le controleur prepare les donnees :

- charge le tournoi de l'utilisateur,
- recupere les rounds,
- determine le round courant et le round selectionne,
- charge les matchs et joueurs,
- construit les donnees du timer,
- transforme le round en payload exploitable par JavaScript.

La vue `tournaments/show.blade.php` transmet ensuite certaines donnees au JS via des attributs `data-*`.

## Generation d'un round

Route :

- `POST /tournaments/{tournament}/rounds/generate`

Traitement :

1. `TournamentController@generateRound` verifie que le tournoi appartient a l'utilisateur.
2. Il lit les options comme `allow_2v1` ou `allow_1v1`.
3. Il verifie qu'il y a assez de joueurs ou assez d'equipes completes.
4. Il appelle `RoundGenerator@generate`.
5. Le service cree le round, les matchs et les joueurs en attente.
6. La reponse est soit JSON, soit une redirection.

Cote navigateur, `public/js/tournaments/round.js` intercepte le formulaire et envoie une requete AJAX. Si la generation reussit, l'utilisateur est redirige vers le round genere.

## Suppression d'un round

Route :

- `DELETE /tournaments/{tournament}/rounds/{round}`

Le controleur :

- verifie le proprietaire du tournoi,
- verifie que le round appartient bien au tournoi,
- lance une transaction,
- retire l'impact des scores sur les points,
- supprime le round et ses donnees liees.

Grace aux suppressions en cascade, les matchs et liaisons associes sont supprimes proprement.

## Enregistrement d'un score

Route :

- `POST /tournaments/{tournament}/matches/{match}/score`

Traitement :

1. controle d'appartenance du tournoi,
2. verification que le match appartient bien au tournoi,
3. validation des deux scores,
4. appel de `MatchScoreRecorder`,
5. retour JSON ou redirection.

`MatchScoreRecorder` evite le double comptage : si un score existe deja, il retire d'abord l'ancien impact, puis applique le nouveau score.

## Joueurs

Routes :

- `GET /tournaments/{tournament}/players`
- `POST /tournaments/{tournament}/players`
- `PATCH /tournaments/{tournament}/players/{player}/points`
- `DELETE /tournaments/{tournament}/players/{player}`

Fonctionnement :

- ajouter un joueur,
- lister les joueurs,
- retirer un joueur,
- corriger manuellement ses points.

Les joueurs utilisent `SoftDeletes`, donc un joueur peut etre retire sans casser l'historique des matchs.

## Equipes

Routes :

- `GET /tournaments/{tournament}/teams`
- `POST /tournaments/{tournament}/teams`
- `PATCH /tournaments/{tournament}/teams/display`
- `PATCH /tournaments/{tournament}/teams/{team}`
- `DELETE /tournaments/{tournament}/teams/{team}`

Ces routes ne sont utiles que pour les tournois au format `team` avec `team_assignment_mode = predefined`.

Le controleur permet :

- ajouter une equipe,
- donner un nom d'equipe optionnel,
- ajouter 2, 3 ou plus joueurs dans une equipe,
- modifier l'equipe,
- supprimer l'equipe,
- choisir l'affichage par nom d'equipe ou par joueurs.

## Points et final

Routes :

- `GET /tournaments/{tournament}/points`
- `GET /tournaments/{tournament}/final`

La page `points` affiche le classement courant.
La page `final` affiche un bilan final avec classement, podium et statistiques.

Le controleur sait gerer deux cas :

- classement par joueur,
- classement par equipe predefinie.

## Parametres

Routes :

- `GET /tournaments/{tournament}/settings`
- `PATCH /tournaments/{tournament}/settings`
- `POST /tournaments/{tournament}/reset`

Les parametres permettent de modifier :

- nom,
- date,
- nombre de terrains,
- duree du round,
- format,
- options 1v1 / 2v1,
- description,
- son d'alarme.

Le reset supprime les rounds et matchs, remet les points a zero et repasse le tournoi en brouillon.
