# TournaBad - dossier de revision

Ce dossier sert a comprendre ton projet sans devoir relire tout le code.

L'idee importante : TournaBad est une application Laravel qui aide un organisateur a gerer un tournoi interne de badminton. Laravel gere les routes, les controles, les vues, les sessions, la securite et l'acces a la base. Ton code ajoute la logique metier : tournois, joueurs, rounds, matchs, scores, equipes, timer et classement.

## Comment lire les fichiers

1. `01_Fonctionnement_global_Laravel.md`
   - Le fonctionnement general de Laravel et le cycle d'une requete.

2. `02_Dossiers_et_fichiers_du_projet.md`
   - A quoi sert chaque dossier important du projet.

3. `03_Pages_routes_controleurs.md`
   - Les pages de l'application et quelles routes/controleurs les gerent.

4. `04_Base_modeles_services_metier.md`
   - Les tables, modeles Eloquent, relations et services importants.

5. `05_Securite_tests_deploiement.md`
   - Les protections, les tests automatises et la mise en production.

6. `06_Aide_oral_questions_jury.md`
   - Une version beaucoup plus orale, avec des phrases simples a ressortir.

## Le plan mental a garder

Si tu paniques, reviens a ce plan :

1. Le besoin : organiser plus facilement des tournois internes de badminton.
2. Laravel : recoit les requetes, appelle les controleurs, affiche les vues et dialogue avec la base.
3. Le coeur metier : generer des rounds equitables et enregistrer les scores sans erreur.
4. La securite : authentification, verification du proprietaire, validation, CSRF, hash des mots de passe.
5. La qualite : tests automatises, transactions, contraintes de base, deploiement.

Tu n'as pas besoin de reciter tes notes. Le jury veut surtout voir que tu comprends les choix et que tu sais expliquer le trajet d'une action dans ton application.
