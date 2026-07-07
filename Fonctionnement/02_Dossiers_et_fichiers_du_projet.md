# Dossiers et fichiers importants

## `routes/`

Contient les routes de l'application.

- `routes/web.php` : toutes les routes web principales.
- Les routes publiques : accueil, mentions legales, confidentialite.
- Les routes `guest` : connexion et inscription.
- Les routes `auth` : compte, tournois, joueurs, rounds, scores, equipes.

## `app/Http/Controllers/`

Contient les controleurs. Ils recoivent les requetes et coordonnent la reponse.

- `AuthController.php` : connexion, inscription, deconnexion.
- `HomeController.php` : accueil, tableau de bord, profil, mot de passe, pages legales.
- `TournamentController.php` : tout le coeur applicatif des tournois.

Le controleur ne devrait pas etre juste un fichier qui affiche une page. Dans ton projet, il valide les donnees, verifie les droits, prepare les donnees pour les vues, puis delegue les traitements complexes aux services.

## `app/Models/`

Contient les modeles Eloquent. Ils representent les tables de la base et leurs relations.

- `User` : compte utilisateur.
- `Tournament` : tournoi cree par un utilisateur.
- `Player` : joueur inscrit dans un tournoi.
- `Round` : tour/round genere.
- `TournamentMatch` : match sur un terrain.
- `MatchPlayer` : liaison entre match et joueur avec le numero d'equipe.
- `MatchScore` : score final d'un match.
- `RoundWaitingPlayer` : joueur en attente pendant un round.
- `TournamentTeam` : equipe definie dans les tournois par equipes.

## `app/Services/`

Contient la logique metier complexe.

- `RoundGenerator.php` : genere les rounds, choisit les joueurs en attente, forme les equipes/matchs, limite les repetitions.
- `MatchScoreRecorder.php` : enregistre ou corrige un score, met a jour les points avec un delta.

C'est un bon choix d'architecture : les traitements sensibles sont isoles et testables.

## `resources/views/`

Contient les vues Blade, donc les pages HTML generees par Laravel.

- `layouts/app.blade.php` : layout global, navigation, styles, theme clair/sombre, footer, token CSRF.
- `home.blade.php` : accueil public.
- `start.blade.php` : accueil connecte.
- `dashboard.blade.php` : compte utilisateur.
- `dashboard-tournaments.blade.php` : tournois depuis le compte.
- `auth/login.blade.php` et `auth/register.blade.php` : connexion et inscription.
- `legal/` : mentions legales et confidentialite.
- `tournaments/` : toutes les pages metier des tournois.

## `resources/views/tournaments/`

Pages metier principales :

- `index.blade.php` : liste des tournois.
- `create.blade.php` : creation d'un tournoi.
- `show.blade.php` : page principale du tournoi, timer, rounds, matchs, scores.
- `settings.blade.php` : parametres du tournoi.
- `players.blade.php` : gestion des joueurs.
- `teams.blade.php` : gestion des equipes predefinies.
- `points.blade.php` : classement et points.
- `final.blade.php` : bilan final.
- `_rounds_menu.blade.php` : menu/historique des rounds.

## `public/js/tournaments/`

JavaScript de la page tournoi.

- `index.js` : point d'entree, initialise les modules.
- `round.js` : gere la generation d'un round en AJAX.
- `matches.js` : affiche les matchs et enregistre les scores.
- `timer.js` : chrono local avec pause, reprise, reset.
- `audio.js` : alarme sonore et vibration.
- `utils.js` : fonctions communes, echappement HTML, formatage du temps, localStorage.

## `database/migrations/`

Contient l'historique de creation/modification des tables.

Les migrations permettent de reconstruire la base sans creer manuellement les tables.

Tables principales :

- `users`
- `tournaments`
- `players`
- `rounds`
- `tournament_matches`
- `match_players`
- `round_waiting_players`
- `match_scores`
- `tournament_teams`
- `tournament_team_players`
- `sessions`, `cache`, `jobs`

## `tests/`

Contient les tests automatises.

- `tests/Unit` : tests plus isoles, par exemple le hash du mot de passe.
- `tests/Feature` : tests de parcours Laravel, routes, controleurs, services, base.

Les tests utilisent `RefreshDatabase`, ce qui remet la base dans un etat propre entre les scenarios.

## `config/`

Configuration Laravel.

- `config/session.php` : duree de session, cookies `http_only`, politique `same_site`.
- `config/database.php` : connexions base.
- `config/app.php` : configuration generale.
- `config/filesystems.php` : stockage, notamment images de profil.

## `public/`

Fichiers accessibles directement par le navigateur.

- `public/index.php` : point d'entree de Laravel en production.
- `public/js/tournaments` : JavaScript de l'application.
- `public/audio` : sons d'alarme.
- `public/image` : images publiques.

En production, le serveur web doit pointer vers `public`, pas vers la racine du projet.
