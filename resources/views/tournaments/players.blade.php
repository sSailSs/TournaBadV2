@extends('layouts.app')

@section('content')
    <section class="grid grid-2">
        <article class="card">
            <h1>Gestion des joueurs</h1>
            <p>{{ $tournament->name }}</p>

            <form method="POST" action="{{ route('tournaments.players.store', $tournament) }}">
                @csrf

                <label for="first_name">Prénom</label>
                <input id="first_name" type="text" name="first_name" value="{{ old('first_name') }}" required>

                <button class="btn btn-primary" style="margin-top:1rem;" type="submit">Ajouter le joueur</button>
            </form>
        </article>

        <article class="card">
            <h2>Conseil organisation</h2>
            <p>
                Pour l'étape actuelle, gère la liste des joueurs ici, au niveau du tournoi.
                Ensuite, quand tu ajoutes les matchs/tours, tu réutiliseras cette liste pour composer les équipes.
            </p>
            <a class="btn btn-outline" href="{{ route('tournaments.show', $tournament) }}">Retour détail tournoi</a>
        </article>
    </section>

    <section class="card" style="margin-top:1rem;">
        <h2>Liste des joueurs</h2>

        @if ($players->isEmpty())
            <p>Aucun joueur ajouté pour le moment.</p>
        @else
            <table class="table">
                <thead>
                    <tr>
                        <th>Joueur</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($players as $player)
                        <tr>
                            <td>{{ $player->full_name }}</td>
                            <td>
                                <form method="POST" action="{{ route('tournaments.players.destroy', [$tournament, $player]) }}" onsubmit="return confirm('Retirer ce joueur ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline" type="submit">Retirer</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div style="margin-top:1rem;">
                {{ $players->links() }}
            </div>
        @endif
    </section>
@endsection
