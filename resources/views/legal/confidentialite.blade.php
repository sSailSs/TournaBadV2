@extends('layouts.app')

@section('content')
    <section class="card">
        <h1 style="margin-bottom:.35rem;">Politique de confidentialité</h1>
        <p style="margin-top:0;">Cette page décrit de façon simple comment les données sont traitées sur TournaBad.</p>
    </section>

    <section class="grid grid-2" style="margin-top:1rem;">
        <article class="card">
            <h2 style="margin-top:0;">Données collectées</h2>
            <p>TournaBad peut traiter les informations suivantes: identifiants de compte, données de profil, tournois créés, joueurs ajoutés, scores et historiques de tournois.</p>
        </article>

        <article class="card">
            <h2 style="margin-top:0;">Finalités</h2>
            <p>Les données sont traitées pour permettre la création de comptes, la gestion des tournois, l'enregistrement des scores, l'affichage des classements et l'administration du service.</p>
        </article>
    </section>

    <section class="grid grid-2" style="margin-top:1rem;">
        <article class="card">
            <h2 style="margin-top:0;">Base légale</h2>
            <p>Le traitement repose sur l'exécution du service demandé par l'utilisateur, sur l'intérêt légitime d'exploitation du site, et sur le consentement lorsque cela est nécessaire.</p>
        </article>

        <article class="card">
            <h2 style="margin-top:0;">Conservation</h2>
            <p>Les données sont conservées pendant la durée nécessaire au fonctionnement du service, puis supprimées ou anonymisées selon les besoins de l'exploitation.</p>
        </article>
    </section>

    <section class="grid grid-2" style="margin-top:1rem;">
        <article class="card">
            <h2 style="margin-top:0;">Droits des personnes</h2>
            <p>Conformément au RGPD, toute personne peut demander l'accès, la rectification, la suppression, la portabilité, la limitation ou l'opposition au traitement de ses données selon les cas applicables.</p>
        </article>

        <article class="card">
            <h2 style="margin-top:0;">Contact RGPD</h2>
            <p>Pour exercer ces droits, contacte l'éditeur du site avec l'adresse indiquée dans les mentions légales.</p>
        </article>
    </section>

    <section class="card" style="margin-top:1rem;">
        <h2 style="margin-top:0;">Cookies techniques</h2>
        <p>Le site peut utiliser des cookies ou stockages techniques strictement nécessaires à la session, à l'authentification et à la sécurité. Aucun traceur publicitaire n'est nécessaire au fonctionnement de base de l'application.</p>
    </section>
@endsection
