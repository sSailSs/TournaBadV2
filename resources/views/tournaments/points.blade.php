@extends('layouts.app')

@section('content')
    <section class="card">
        <div style="display:flex; align-items:start; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
            <div>
                <h1 style="margin-bottom:.4rem;">Points individuels</h1>
                <p style="margin:0;">{{ $tournament->name }} | Chaque score saisi sur un match alimente les points, victoires et défaites.</p>
            </div>
            <a class="btn btn-outline" href="{{ route('tournaments.show', $tournament) }}">Retour au tournoi</a>
        </div>
    </section>

    <section class="card" style="margin-top:1rem;">
        <table class="table">
            <thead>
                <tr>
                    <th>Joueur</th>
                    <th>Points</th>
                    <th>Victoires</th>
                    <th>Défaites</th>
                    <th>Attentes</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($players as $player)
                    <tr>
                        <td>{{ $player->full_name ?: $player->first_name }}</td>
                        <td>{{ $player->points }}</td>
                        <td>{{ $player->wins ?? 0 }}</td>
                        <td>{{ $player->losses ?? 0 }}</td>
                        <td>{{ $player->waiting_count ?? 0 }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">Aucun joueur pour le moment.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
@endsection