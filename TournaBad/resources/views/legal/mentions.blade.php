@extends('layouts.app')

@section('content')
    <section class="card">
        <h1 style="margin-bottom:.35rem;">Mentions legales</h1>
        <p style="margin-top:0;">Les informations ci-dessous sont une base a completer selon l'identite exacte de l'editeur et de l'hebergeur.</p>
    </section>

    <section class="grid grid-2" style="margin-top:1rem;">
        <article class="card">
            <h2 style="margin-top:0;">Editeur du site</h2>
            <p>Le site TournaBad est edite par son proprietaire.</p>
            <p><strong>Editeur :</strong> [a completer]</p>
            <p><strong>Adresse :</strong> [a completer]</p>
            <p><strong>Contact :</strong> [a completer]</p>
        </article>

        <article class="card">
            <h2 style="margin-top:0;">Hebergement</h2>
            <p><strong>Hebergeur :</strong> [a completer]</p>
            <p><strong>Adresse :</strong> [a completer]</p>
            <p><strong>Telephone :</strong> [a completer]</p>
        </article>
    </section>

    <section class="card" style="margin-top:1rem;">
        <h2 style="margin-top:0;">Propriete intellectuelle</h2>
        <p>Le contenu, le code, le design et les elements graphiques du site sont proteges par le droit d'auteur et restent la propriete de leur titulaire.</p>
        <p>Toute reproduction, diffusion, adaptation ou reutilisation sans autorisation ecrite est interdite.</p>
        <p>Le nom TournaBad et les contenus associes peuvent egalement etre proteges au titre des droits de marque ou de l'identite visuelle.</p>
    </section>

    <section class="card" style="margin-top:1rem;">
        <h2 style="margin-top:0;">Responsabilite</h2>
        <p>L'editeur s'efforce de fournir des informations exactes et de maintenir le service disponible, sans toutefois garantir l'absence totale d'erreur ou d'interruption.</p>
        <p>L'utilisateur reste responsable des donnees qu'il saisit et de l'usage qu'il fait du service.</p>
    </section>

    <section class="card" style="margin-top:1rem;">
        <h2 style="margin-top:0;">Donnees personnelles</h2>
        <p>Les donnees collectées sur TournaBad sont utilisées uniquement pour la gestion des comptes, des tournois et du fonctionnement du service.</p>
        <p>Conformément au RGPD, les personnes concernées peuvent demander l'acces, la rectification, l'opposition, la limitation ou la suppression de leurs donnees, selon les cas applicables.</p>
        <p><strong>Base legale :</strong> execution du service, interet legitime de gestion et, si necessaire, consentement de l'utilisateur.</p>
    </section>

    <section class="card" style="margin-top:1rem;">
        <h2 style="margin-top:0;">Cookies et traces techniques</h2>
        <p>Le site peut utiliser des cookies ou des donnees de session strictement necessaires a l'authentification, a la navigation et au fonctionnement de l'application.</p>
        <p>Si tu ajoutes des outils de mesure d'audience ou des services tiers, cette section devra etre completee en consequence.</p>
    </section>

    <section class="card" style="margin-top:1rem;">
        <h2 style="margin-top:0;">Contact</h2>
        <p>Pour toute demande relative aux mentions legales ou aux donnees personnelles, utilise le contact de l'editeur indique ci-dessus.</p>
    </section>
@endsection