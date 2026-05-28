@extends('layouts.app')

@section('content')
    <section class="card">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
            <div>
                <h1 style="margin-bottom:.4rem;">Mes tournois</h1>
                <p style="margin:0;">Commence ici, puis ouvre le detail pour gerer les joueurs.</p>
            </div>
            <a class="btn btn-primary" href="{{ route('tournaments.create') }}">Creer un tournoi</a>
        </div>

        @if ($tournaments->isEmpty())
            <p style="margin-top:1rem;">Aucun tournoi pour le moment.</p>
        @else
            <table class="table" style="margin-top:1rem;">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Mode</th>
                        <th>Date</th>
                        <th>Terrains</th>
                        <th>Duree / tour</th>
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
                                <a class="btn btn-outline" href="{{ route('tournaments.show', $tournament) }}">Detail</a>
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
@endsection
