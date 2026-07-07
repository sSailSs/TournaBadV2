# Base de donnees, modeles et services metier

## Vision globale de la base

La base stocke :

- les utilisateurs,
- les tournois,
- les joueurs,
- les rounds,
- les matchs,
- les scores,
- les joueurs en attente,
- les equipes predefinies.

Les migrations creent les tables et ajoutent progressivement les options du projet.

## Tables principales

### `users`

Stocke les comptes.

Champs importants :

- `name`
- `email`
- `password`
- `profile_photo_path`
- `is_admin`
- `remember_token`

Le mot de passe est hashe automatiquement par le cast Laravel `password => hashed`.

### `tournaments`

Stocke les tournois.

Champs importants :

- `creator_id` : utilisateur qui a cree le tournoi.
- `name`
- `starts_on`
- `courts_count`
- `round_duration_minutes`
- `round_duration_seconds`
- `format` : double, single, mixed, team.
- `allow_2v1`
- `allow_1v1`
- `alarm_audio_path`
- `team_assignment_mode`
- `team_size`
- `team_display_mode`
- `status`
- `description`

Un tournoi appartient a un utilisateur et contient joueurs, rounds, matchs et equipes.

### `players`

Stocke les joueurs d'un tournoi.

Champs importants :

- `tournament_id`
- `first_name`
- `is_active`
- `points`
- `manual_points_adjustment`
- `created_by`
- `deleted_at`

Les joueurs utilisent `SoftDeletes`. Cela permet de retirer un joueur tout en gardant les anciens matchs coherents.

### `rounds`

Stocke les rounds generes.

Champs importants :

- `tournament_id`
- `round_number`
- `status`
- `generated_at`
- `started_at`
- `ended_at`

Contrainte importante :

- un meme tournoi ne peut pas avoir deux rounds avec le meme `round_number`.

### `tournament_matches`

Stocke les matchs.

Champs importants :

- `round_id`
- `tournament_id`
- `court_number`
- `match_type`
- `status`

Contrainte importante :

- dans un meme round, un terrain ne peut avoir qu'un seul match.

### `match_players`

Table de liaison entre un match et ses joueurs.

Champs importants :

- `tournament_match_id`
- `player_id`
- `team_number`

Elle permet de savoir quels joueurs sont dans le match et dans quelle equipe.

### `match_scores`

Stocke le score final d'un match.

Champs importants :

- `tournament_match_id`
- `team_one_score`
- `team_two_score`
- `recorded_by`
- `recorded_at`

Contrainte importante :

- un match ne peut avoir qu'un score final.

### `round_waiting_players`

Stocke les joueurs qui attendent pendant un round.

Champs importants :

- `round_id`
- `player_id`

Contrainte importante :

- un joueur ne peut pas etre marque deux fois en attente dans le meme round.

### `tournament_teams` et `tournament_team_players`

Servent aux tournois par equipes predefinies.

`tournament_teams` contient l'equipe.
`tournament_team_players` relie l'equipe a ses joueurs avec une position.

## Relations Eloquent importantes

### `User`

- `tournaments()` : les tournois crees par l'utilisateur.
- `createdPlayers()` : les joueurs crees par l'utilisateur.

### `Tournament`

- `creator()` : utilisateur createur.
- `players()` : joueurs du tournoi.
- `rounds()` : rounds du tournoi.
- `matches()` : matchs du tournoi.
- `teams()` : equipes du tournoi.

### `Round`

- `tournament()` : tournoi parent.
- `matches()` : matchs du round.
- `waitingPlayers()` : joueurs en attente.

### `TournamentMatch`

- `round()` : round parent.
- `tournament()` : tournoi parent.
- `players()` : joueurs lies au match via `match_players`.
- `score()` : score du match.

### `TournamentTeam`

- `tournament()` : tournoi parent.
- `players()` : joueurs de l'equipe via `tournament_team_players`.

## Service `RoundGenerator`

C'est le coeur metier le plus important.

Son objectif : generer un round en respectant au mieux :

- le nombre de joueurs,
- le nombre de terrains,
- le format du tournoi,
- les options 1v1 ou 2v1,
- les equipes predefinies,
- l'historique des partenaires,
- l'historique des adversaires,
- les temps d'attente.

### Protection contre les doublons

La generation est dans une transaction SQL.
Le service verrouille le tournoi avec `lockForUpdate`.

But : eviter qu'un double-clic cree deux rounds en meme temps.

Il y a aussi une protection temporelle : si un round vient d'etre genere dans les 3 dernieres secondes, le service renvoie le round existant.

### Generation classique

Le service :

1. recupere les joueurs actifs,
2. calcule le numero du prochain round,
3. determine le meilleur plan de terrains,
4. choisit les joueurs en attente de maniere equitable,
5. analyse l'historique des partenaires/adversaires,
6. forme les doubles en penalissant les repetitions,
7. complete avec du 2v1 ou du 1v1 si autorise,
8. cree le round,
9. cree les matchs,
10. attache les joueurs aux matchs,
11. enregistre les joueurs en attente,
12. retourne un payload JSON exploitable par l'interface.

### Choix des joueurs en attente

La methode de selection favorise les joueurs qui ont le moins attendu.

Elle tient aussi compte :

- du dernier round attendu,
- des joueurs ajoutes recemment,
- d'un tirage aleatoire pour departager.

Objectif : eviter que les memes personnes attendent souvent.

### Choix des partenaires et adversaires

Le service construit deux historiques :

- partenaires deja associes,
- adversaires deja rencontres.

Ensuite il attribue des scores aux combinaisons.

Plus une association est deja apparue, plus son score est eleve, donc moins elle est choisie.

En simplifiant a l'oral :

> Je ne fais pas un simple shuffle. Je garde l'historique des associations et je penalise les combinaisons deja vues pour favoriser la rotation.

### Equipes predefinies

Pour les tournois avec equipes fixes :

- seules les equipes completes peuvent jouer,
- les joueurs d'une equipe restent ensemble,
- si l'equipe contient plus de 2 joueurs, deux joueurs sont selectionnes pour le match,
- l'algorithme limite aussi les repetitions entre equipes adverses,
- quand c'est possible, il utilise une logique proche d'un round robin.

## Service `MatchScoreRecorder`

Ce service enregistre les scores et met a jour les points.

Pourquoi un service ?

Parce que la correction d'un score est sensible.

### Premiere saisie

Si aucun score n'existe :

1. le service cree un `MatchScore`,
2. il ajoute les points de l'equipe 1 aux joueurs de l'equipe 1,
3. il ajoute les points de l'equipe 2 aux joueurs de l'equipe 2.

### Correction d'un score

Si un score existe deja :

1. le service retire l'ancien score des points des joueurs,
2. il met a jour la ligne `match_scores`,
3. il applique le nouveau score.

Cela evite le double comptage.

Exemple :

- ancien score : 11-7,
- nouveau score : 9-11.

Le service retire d'abord 11 et 7, puis ajoute 9 et 11.

Tout est fait dans une transaction.

## Calcul des classements

Le classement est construit dans `TournamentController` avec :

- `buildPlayerStats` pour les classements par joueurs,
- `buildTeamStats` pour les classements par equipes.

Les statistiques prennent en compte :

- points,
- victoires,
- defaites,
- temps d'attente,
- ajustements manuels.

Pour les equipes predefinies, les points sont affiches par equipe et pas multiplies par le nombre de joueurs.
