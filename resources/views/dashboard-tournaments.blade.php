@extends('layouts.app')

@section('content')
    @php
        $activeAccountMenu = 'tournaments';
    @endphp

    @include('dashboard._account_nav', ['activeAccountMenu' => $activeAccountMenu])

    <div class="account-page-wrap">
        <section class="card">
            <div style="display:flex; align-items:start; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
                <div>
                    <h1 style="margin-bottom:.35rem;">Tournois</h1>
                    <p style="margin:0;">Tous tes tournois crees, en cours ou deja faits.</p>
                </div>
                <a class="btn btn-primary" href="{{ route('tournaments.create') }}">Creer un tournoi</a>
            </div>
        </section>

        <section class="grid grid-3" style="margin-top:1rem;">
            <article class="card">
                <h3>Total</h3>
                <p><strong>{{ $stats['my_tournaments'] }}</strong> tournoi(x)</p>
            </article>

            <article class="card">
                <h3>En cours</h3>
                <p><strong>{{ $stats['ongoing_tournaments'] }}</strong> tournoi(x)</p>
            </article>

            <article class="card">
                <h3>Faits</h3>
                <p><strong>{{ $stats['past_tournaments'] }}</strong> tournoi(x)</p>
            </article>
        </section>

        <section class="card" style="margin-top:1rem;">
            <div style="display:flex; align-items:start; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
                <div>
                    <h2 style="margin-bottom:.35rem;">Mes tournois</h2>
                    <p style="margin-top:0;">Ouvre, modifie, ou supprime un tournoi.</p>
                </div>
                <a class="btn btn-outline" href="{{ route('tournaments.index') }}">Voir la page Tournois</a>
            </div>

            @if ($tournaments->isEmpty())
                <p style="margin-top:1rem;">Aucun tournoi pour le moment.</p>
            @else
                <table class="table" style="margin-top:1rem;">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Date</th>
                            <th>Terrains</th>
                            <th>Joueurs</th>
                            <th>Etat</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tournaments as $tournament)
                            @php
                                $isPastTournament = $tournament->starts_on && $tournament->starts_on->isPast() && ! $tournament->starts_on->isToday();
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('tournaments.show', $tournament) }}">{{ $tournament->name }}</a>
                                </td>
                                <td>{{ $tournament->starts_on?->format('d/m/Y') }}</td>
                                <td>{{ $tournament->courts_count }}</td>
                                <td>{{ $tournament->players_count }}</td>
                                <td>
                                    <span class="tournament-status-pill">{{ $isPastTournament ? 'Fait' : 'En cours' }}</span>
                                </td>
                                <td style="display:flex; gap:.5rem; flex-wrap:wrap;">
                                    <a class="btn btn-outline" href="{{ route('tournaments.show', $tournament) }}">Ouvrir</a>
                                    <a class="btn btn-outline" href="{{ route('tournaments.settings', $tournament) }}">Modifier</a>
                                    <form method="POST" action="{{ route('tournaments.destroy', $tournament) }}" onsubmit="return confirm('Supprimer ce tournoi ? Cela supprimera aussi les tours, matchs et joueurs.');" style="margin:0;">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline" type="submit" style="border-color: color-mix(in srgb, var(--danger) 35%, var(--line) 65%); color: var(--danger);">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div style="margin-top:1rem;">
                    {{ $tournaments->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection
