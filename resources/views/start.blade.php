@extends('layouts.app')

@section('content')
    <section class="hero">
        <p class="badge" style="margin:0 0 0.85rem; width:fit-content;">Accueil</p>
        <h1 style="margin-bottom:.35rem;">Bonjour {{ $user->name }} 👋</h1>
        <p style="color: rgba(255,255,255,0.9); max-width: 780px; margin-bottom: 0;">
            Choisis ton mode TournaBad. Le mode principal est le tournoi interne en 2v2.
            Le mode en équipe reprend la même base avec des équipes aléatoires ou prédéfinies.
        </p>
    </section>

    <section class="grid grid-3 mode-grid" style="margin-top: 1rem;">
        <a class="card mode-card mode-card-active" href="{{ route('tournaments.create', ['type' => 'team']) }}" style="border-top: 4px solid var(--accent); text-decoration:none; color:inherit;">
            <h2>En équipe</h2>
            <p>Tournoi par équipes avec composition aléatoire ou équipes prédéfinies.</p>
            <span class="badge">Mode actif</span>
        </a>

        <a class="card mode-card mode-card-active" href="{{ route('tournaments.create') }}" style="border-top: 4px solid var(--brand); text-decoration:none; color:inherit;">
            <h2>2 vs 2</h2>
            <p>C'est le mode actuellement implémenté: création du tournoi, joueurs, tirage, scores et points individuels.</p>
            <span class="badge">Mode actif</span>
        </a>

        <article class="card mode-card mode-card-disabled" style="border-top: 4px solid var(--muted); opacity: .72;">
            <h2>Autres paramètres</h2>
            <p>Par exemple un tournoi interne par niveau, par catégorie ou avec des règles spécifiques.</p>
            <span class="badge">À venir</span>
        </article>
    </section>

    <section class="grid grid-3" style="margin-top: 1rem;">
        <article class="card">
            <h3>Mes tournois</h3>
            <p><strong>{{ $stats['my_tournaments'] }}</strong> tournoi(x)</p>
        </article>

        <article class="card">
            <h3>Raccourcis</h3>
            <div class="list" style="margin-top:.45rem;">
                <a class="tag" href="{{ route('tournaments.index') }}">Voir mes tournois</a>
                <a class="tag" href="{{ route('tournaments.create') }}">Créer un tournoi</a>
                <a class="tag" href="{{ route('dashboard') }}">Mon compte</a>
            </div>
        </article>

        <article class="card">
            <h3>Derniers tournois</h3>
            @if ($latestTournaments->isEmpty())
                <p style="margin:0;">Aucun tournoi pour le moment.</p>
            @else
                <div class="match-flat-list" style="margin-top:.35rem;">
                    @foreach ($latestTournaments as $tournament)
                        <div class="match-flat-item" style="padding:.45rem 0;">
                            <a href="{{ route('tournaments.show', $tournament) }}" style="font-weight:800; text-decoration:none;">
                                {{ $tournament->name }}
                            </a>
                            <div class="muted" style="font-size:.92rem; margin-top:.15rem;">
                                {{ $tournament->starts_on?->format('d/m/Y') }} • {{ $tournament->courts_count }} terrain(s)
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </article>
    </section>
@endsection
