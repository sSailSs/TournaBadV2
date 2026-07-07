# Aide pour l'oral et questions possibles

## La phrase simple pour presenter le projet

TournaBad est une application Laravel que j'ai developpee pour organiser des tournois internes de badminton. Elle permet de creer un tournoi, ajouter des joueurs ou des equipes, generer automatiquement des rounds, suivre le chrono, enregistrer les scores et afficher le classement.

## La phrase simple pour expliquer Laravel

Laravel me sert de framework principal. Il gere les routes, les controleurs, les vues Blade, les modeles Eloquent, la validation, l'authentification, les sessions, la protection CSRF et les tests. J'ai construit mes fonctionnalites metier au-dessus de cette structure.

## La phrase simple pour expliquer MVC

Dans le MVC, la vue affiche l'interface, le controleur recoit la requete et coordonne le traitement, et le modele represente les donnees. Dans mon projet, j'ai ajoute des services pour isoler les traitements plus complexes comme la generation d'un round.

## La phrase simple pour expliquer une route

Une route relie une URL a une methode de controleur. Par exemple, quand l'utilisateur demande la generation d'un round, la route appelle `generateRound` dans `TournamentController`, puis le controleur appelle le service `RoundGenerator`.

## La phrase simple pour expliquer Eloquent

Eloquent est l'ORM de Laravel. Il me permet de manipuler les tables sous forme d'objets PHP, par exemple `Tournament`, `Player` ou `Round`, et de definir les relations entre eux.

## La phrase simple pour expliquer RoundGenerator

`RoundGenerator` est le service qui contient la logique la plus importante. Il ne se contente pas de melanger les joueurs : il tient compte du nombre de terrains, du format, de l'historique des partenaires, des adversaires et des temps d'attente pour proposer une repartition plus equitable.

## La phrase simple pour expliquer MatchScoreRecorder

`MatchScoreRecorder` centralise l'enregistrement des scores. Si un score est corrige, il retire d'abord l'ancien impact sur les points puis applique le nouveau score. Cela evite le double comptage.

## La phrase simple pour expliquer la securite

J'ai mis plusieurs niveaux de securite : authentification sur les routes metier, verification que le tournoi appartient bien a l'utilisateur, validation serveur des formulaires, protection CSRF, hash des mots de passe, echappement des donnees affichees et transactions pour les operations sensibles.

## La phrase simple pour expliquer les tests

Les tests automatises verifient les regles critiques : creation des tournois, generation des rounds, rotation des partenaires, equipes predefinies, enregistrement et correction des scores, suppression d'un round et mise a jour manuelle des points.

## Questions possibles du jury

### Pourquoi Laravel ?

Parce que Laravel fournit une structure MVC claire et beaucoup d'outils utiles pour une application de gestion : routes, validation, authentification, sessions, Eloquent, migrations et tests. Cela m'a permis de me concentrer sur les regles metier du tournoi.

### Pourquoi ne pas avoir fait une SPA ?

Le besoin ne justifiait pas une application front-end separee. Blade permet de rendre les pages simplement cote serveur. JavaScript est ajoute seulement la ou il apporte une vraie valeur : timer, score, generation dynamique.

### Comment empeches-tu un utilisateur d'acceder au tournoi d'un autre ?

D'abord, les routes sont protegees par `auth`. Ensuite, dans le controleur, je verifie que `creator_id` du tournoi correspond a l'utilisateur connecte. Si ce n'est pas le cas, je renvoie une erreur 403.

### Comment evites-tu les injections SQL ?

J'utilise Eloquent et le Query Builder, donc les valeurs sont parametrees. Je valide aussi les donnees cote serveur. Je n'insere pas directement une saisie utilisateur dans du SQL brut.

### Comment evites-tu le XSS ?

Blade echappe les donnees affichees avec `{{ }}`. Quand je genere du HTML en JavaScript, je passe les valeurs par une fonction `escapeHtml`.

### Comment fonctionnent les scores ?

Un match a deux equipes. Quand un score est saisi, les joueurs de l'equipe 1 recoivent le score de l'equipe 1, et les joueurs de l'equipe 2 recoivent le score de l'equipe 2. Si le score est corrige, je retire l'ancien avant d'ajouter le nouveau.

### Pourquoi utiliser des transactions ?

Parce que certaines operations modifient plusieurs tables en meme temps. Par exemple, generer un round cree un round, des matchs, des liaisons joueurs-matchs et des joueurs en attente. Avec une transaction, si une etape echoue, tout est annule.

### Pourquoi utiliser des services ?

Pour eviter des controleurs trop volumineux et rendre la logique metier plus claire. Le controleur gere la requete et la reponse, tandis que le service gere le traitement complexe.

### Comment fonctionne le timer ?

Le timer est gere cote navigateur en JavaScript. Il utilise la duree du round envoyee par Laravel, stocke son etat dans `localStorage`, permet de demarrer, mettre en pause et reinitialiser, puis declenche un son a la fin.

### Comment expliques-tu la base de donnees simplement ?

Un utilisateur cree des tournois. Un tournoi contient des joueurs, des rounds et des matchs. Un round contient plusieurs matchs. Les joueurs sont relies aux matchs par une table de liaison qui indique leur equipe. Un match peut avoir un score. Une autre table memorise les joueurs en attente.

## Ce qu'il faut absolument savoir expliquer

- Le trajet d'une requete Laravel.
- La difference entre route, controleur, modele et vue.
- Pourquoi `RoundGenerator` est dans un service.
- Comment tu evites le double comptage des scores.
- Comment tu verifies qu'un utilisateur possede bien son tournoi.
- Pourquoi les transactions sont utiles.
- Ce que testent tes tests automatises.

## Version ultra courte si tu bloques

Si je bloque a l'oral, je peux revenir a cette phrase :

> Mon application suit une architecture Laravel classique : les routes recoivent les requetes, les controleurs verifient les droits et les donnees, les modeles Eloquent communiquent avec la base, les vues Blade affichent le resultat, et les services isolent les regles metier complexes comme la generation des rounds et l'enregistrement des scores.

## Rappel anti-panique

Tu n'as pas besoin de connaitre chaque ligne par coeur.

Le jury veut voir que tu comprends :

- pourquoi tu as fait ces choix,
- comment les grandes parties communiquent,
- comment tu proteges les donnees,
- comment tu testes les regles importantes.

Si tu ne sais plus un detail exact, tu peux dire :

> Je n'ai pas le nom exact de la methode en tete, mais le principe est que le controleur verifie d'abord le tournoi, puis appelle le service ou le modele concerne.

C'est une reponse normale et professionnelle.
