@extends('layouts.app')

@section('content')
    <section class="hero">
        <h1>Organise ton tournoi interne de badminton</h1>
        <p style="color: rgba(255,255,255,0.9); max-width: 780px;">
            TournaBad centralise les joueurs, la creation des tours, les matchs par terrain et le suivi des scores.
            Une fois connecte, tu accedes au choix des modes: 2v2 actif, en equipe, et d'autres parametres a venir.
        </p>
        <div style="display:flex; gap:.7rem; flex-wrap: wrap; margin-top: 1rem;">
            <a class="btn btn-outline" style="background:#fff;" href="{{ route('login') }}">Se connecter</a>
            <a class="btn btn-primary" style="background:#083344;" href="{{ route('register') }}">Commencer</a>
        </div>
    </section>

    <section class="grid grid-3" style="margin-top: 1rem;">
        <article class="card">
            <h3>Tournois</h3>
            <p>Nom, date, nombre de terrains et duree des tours.</p>
        </article>

        <article class="card">
            <h3>Matchs</h3>
            <p>Generation des matchs puis affectation des joueurs par equipe.</p>
        </article>

        <article class="card">
            <h3>Chrono & score</h3>
            <p>Timers de match et suivi des points par terrain.</p>
        </article>
    </section>
@endsection
