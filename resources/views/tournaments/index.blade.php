@extends('layouts.app')

@section('content')
    <section class="card">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
            <div>
                <h1 style="margin-bottom:.4rem;">Mes tournois</h1>
                <p style="margin:0;">Commence ici, puis ouvre le détail pour gérer les joueurs.</p>
            </div>
            <a class="btn btn-primary" href="{{ route('tournaments.create') }}">Créer un tournoi</a>
        </div>

        @if ($tournaments->isEmpty())
            <p style="margin-top:1rem;">Aucun tournoi pour le moment.</p>
        @else
            <table class="table responsive-table-desktop" style="margin-top:1rem;">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Mode</th>
                        <th>Date</th>
                        <th>Terrains</th>
                        <th>Durée / tour</th>
                        <th>Joueurs</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tournaments as $tournament)
                        <tr>
                            <td>{{ $tournament->name }}</td>
                            <td>{{ $tournament->format === 'double' ? '2 vs 2' : ucfirst($tournament->format) }}</td>
                            <td>{{ $tournament->starts_on?->format('d/m/Y') }}</td>
                            <td>{{ $tournament->courts_count }}</td>
                            <td>{{ $tournament->round_duration_minutes }} min</td>
                            <td>{{ $tournament->players_count }}</td>
                            <td>{{ ucfirst($tournament->status) }}</td>
                            <td>
                                <a class="btn btn-outline" href="{{ route('tournaments.show', $tournament) }}">Détail</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="responsive-card-list" style="margin-top:1rem;">
                @foreach ($tournaments as $tournament)
                    <article class="tournament-list-card">
                        <div>
                            <h3 style="margin-bottom:.25rem;">{{ $tournament->name }}</h3>
                            <p class="muted" style="margin:0;">
                                {{ $tournament->format === 'double' ? '2 vs 2' : ucfirst($tournament->format) }}
                                | {{ $tournament->starts_on?->format('d/m/Y') }}
                            </p>
                        </div>

                        <div class="tournament-list-meta">
                            <span class="tag">{{ $tournament->players_count }} joueur(s)</span>
                            <span class="tag">{{ ucfirst($tournament->status) }}</span>
                        </div>

                        <a class="btn btn-primary" href="{{ route('tournaments.show', $tournament) }}">Ouvrir</a>
                    </article>
                @endforeach
            </div>

            <div style="margin-top:1rem;">
                {{ $tournaments->links() }}
            </div>
        @endif
    </section>
@endsection
