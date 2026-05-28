@extends('layouts.app')

@section('content')
    <section class="card">
        <h1 style="margin-bottom:.35rem;">Politique de confidentialite</h1>
        <p style="margin-top:0;">Cette page decrit de facon simple comment les donnees sont traitees sur TournaBad.</p>
    </section>

    <section class="grid grid-2" style="margin-top:1rem;">
        <article class="card">
            <h2 style="margin-top:0;">Donnees collectées</h2>
            <p>TournaBad peut traiter les informations suivantes: identifiants de compte, donnees de profil, tournois crees, joueurs ajoutes, scores et historiques de tournois.</p>
        </article>

        <article class="card">
            <h2 style="margin-top:0;">Finalites</h2>
            <p>Les donnees sont traitees pour permettre la creation de comptes, la gestion des tournois, l'enregistrement des scores, l'affichage des classements et l'administration du service.</p>
        </article>
    </section>

    <section class="grid grid-2" style="margin-top:1rem;">
        <article class="card">
            <h2 style="margin-top:0;">Base legale</h2>
            <p>Le traitement repose sur l'execution du service demande par l'utilisateur, sur l'interet legitime d'exploitation du site, et sur le consentement lorsque cela est necessaire.</p>
        </article>

        <article class="card">
            <h2 style="margin-top:0;">Conservation</h2>
            <p>Les donnees sont conservees pendant la duree necessaire au fonctionnement du service, puis supprimees ou anonymisees selon les besoins de l'exploitation.</p>
        </article>
    </section>

    <section class="grid grid-2" style="margin-top:1rem;">
        <article class="card">
            <h2 style="margin-top:0;">Droits des personnes</h2>
            <p>Conformement au RGPD, toute personne peut demander l'acces, la rectification, la suppression, la portabilite, la limitation ou l'opposition au traitement de ses donnees selon les cas applicables.</p>
        </article>

        <article class="card">
            <h2 style="margin-top:0;">Contact RGPD</h2>
            <p>Pour exercer ces droits, contacte l'editeur du site avec l'adresse indiquee dans les mentions legales.</p>
        </article>
    </section>

    <section class="card" style="margin-top:1rem;">
        <h2 style="margin-top:0;">Cookies techniques</h2>
        <p>Le site peut utiliser des cookies ou stockages techniques strictement necessaires a la session, a l'authentification et a la securite. Aucun traceur publicitaire n'est necessaire au fonctionnement de base de l'application.</p>
    </section>
@endsection