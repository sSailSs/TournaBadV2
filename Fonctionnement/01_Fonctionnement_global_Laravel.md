# Fonctionnement global de Laravel dans TournaBad

## Role de Laravel

Laravel est le framework PHP qui structure l'application. Il fournit :

- un systeme de routes dans `routes/web.php`,
- des controleurs dans `app/Http/Controllers`,
- des modeles Eloquent dans `app/Models`,
- des vues Blade dans `resources/views`,
- des migrations dans `database/migrations`,
- l'authentification, les sessions, la validation, les redirections et la protection CSRF,
- des outils de test avec PHPUnit.

Dans TournaBad, Laravel sert de socle. Il gere le fonctionnement web classique, et ton code ajoute les regles propres au badminton.

## Cycle d'une requete

Exemple general :

1. L'utilisateur clique sur un bouton ou ouvre une URL.
2. Le navigateur envoie une requete HTTP.
3. Laravel cherche la route correspondante dans `routes/web.php`.
4. La route appelle une methode de controleur.
5. Le controleur valide les donnees, verifie les droits, appelle les modeles ou services.
6. Les modeles Eloquent lisent ou ecrivent dans la base.
7. Le controleur renvoie une vue Blade, une redirection ou une reponse JSON.
8. Le navigateur affiche le resultat.

## MVC dans le projet

TournaBad suit une organisation MVC :

- Modele : les classes dans `app/Models`, par exemple `Tournament`, `Player`, `Round`, `TournamentMatch`.
- Vue : les fichiers Blade dans `resources/views`, par exemple `tournaments/show.blade.php`.
- Controleur : les classes dans `app/Http/Controllers`, par exemple `TournamentController`.

Tu as aussi ajoute des services metier dans `app/Services`. C'est important : cela evite de mettre toute la logique complexe dans le controleur.

Exemple :

- `TournamentController` recoit la demande de generation d'un round.
- `RoundGenerator` contient la logique de repartition des joueurs.
- Les modeles enregistrent le round, les matchs et les joueurs en attente.
- La vue affiche le programme.

## Rendu serveur et JavaScript cible

L'application n'est pas une SPA. La plupart des pages sont rendues cote serveur avec Blade.

JavaScript est utilise seulement pour les interactions qui doivent etre rapides :

- generation d'un round sans recharger inutilement toute la page,
- affichage dynamique des matchs,
- saisie des scores en AJAX,
- timer local,
- alarme sonore,
- menus et modales.

Cette approche limite la complexite : Laravel garde le controle principal, JavaScript ameliore l'experience utilisateur.

## Technologies utilisees

- PHP 8.2 : langage serveur.
- Laravel 12 : framework MVC, routes, validation, auth, Eloquent.
- Blade : moteur de templates Laravel.
- MySQL/MariaDB : base de donnees de l'application.
- SQLite en test : base isolee pour les tests automatises.
- Composer : dependances PHP.
- NPM/Vite : ressources front-end.
- JavaScript vanilla : interactions du tournoi.
- CSS/Tailwind/Vite : partie style et build front.
- PHPUnit : tests unitaires et feature.
