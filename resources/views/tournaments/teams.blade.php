@extends('layouts.app')

@section('content')
    <section class="grid grid-2">
        <article class="card">
            <h1>Gestion des equipes</h1>
            <p>{{ $tournament->name }}</p>

            <form method="POST" action="{{ route('tournaments.teams.store', $tournament) }}" data-team-form>
                @csrf

                <label for="team_label">Nom d'equipe optionnel</label>
                <input id="team_label" type="text" name="team_label" value="{{ old('team_label') }}" placeholder="Ex: Les Smashers">

                <div data-player-list style="display:grid; gap:.65rem; margin-top:.75rem;">
                    <label style="margin-top:0;">Joueurs</label>
                    <input type="text" name="player_names[]" value="{{ old('player_names.0') }}" placeholder="Joueur 1" required>
                    <input type="text" name="player_names[]" value="{{ old('player_names.1') }}" placeholder="Joueur 2" required>
                </div>

                <div style="display:flex; gap:.65rem; flex-wrap:wrap; margin-top:1rem;">
                    <button class="btn btn-outline" type="button" data-add-player>Ajouter joueur</button>
                    <button class="btn btn-primary" type="submit">Ajouter l'equipe</button>
                </div>
            </form>
        </article>

        <article class="card">
            <h2>Principe</h2>
            <p>
                Chaque equipe peut contenir autant de joueurs que necessaire. A chaque tour, deux joueurs sont tires dans chaque equipe pour jouer le match.
                Si une equipe a moins de deux joueurs, elle est ignoree jusqu'a ce que tu la completes.
            </p>
            <a class="btn btn-outline" href="{{ route('tournaments.show', $tournament) }}">Retour detail tournoi</a>
        </article>
    </section>

    <section style="margin-top:1rem;">
        <div class="section-title">
            <div>
                <h2 style="margin-bottom:.35rem;">Liste des equipes</h2>
                <p class="muted" style="margin:0;">Nom d'equipe, joueurs presents et actions rapides.</p>
            </div>
        </div>

        @if ($teams->isEmpty())
            <section class="card">
                <p>Aucune equipe ajoutee pour le moment.</p>
            </section>
        @else
            <div class="grid grid-2">
                @foreach ($teams as $team)
                    @php
                        $teamPlayers = $team->players;
                        $teamPlayerNames = $teamPlayers->map(fn ($player) => $player->full_name ?: $player->first_name)->values();
                    @endphp

                    <article class="card">
                        <div>
                            <h3 style="margin-bottom:.35rem;">{{ $team->team_label ?: $teamPlayerNames->implode(' / ') }}</h3>
                            @if ($team->team_label)
                                <p class="muted" style="margin:.2rem 0 0;">{{ $teamPlayerNames->implode(' / ') }}</p>
                            @endif
                            @if ($teamPlayers->count() < 2)
                                <span class="badge" style="margin-top:.5rem;">Incomplete</span>
                            @endif
                        </div>

                        <form id="updateTeam{{ $team->id }}" method="POST" action="{{ route('tournaments.teams.update', [$tournament, $team]) }}" data-team-form style="display:grid; gap:.65rem; margin-top:1rem;">
                            @csrf
                            @method('PATCH')

                            <input type="text" name="team_label" value="{{ old('team_label', $team->team_label) }}" placeholder="Nom d'equipe optionnel">

                            <div data-player-list style="display:grid; gap:.55rem;">
                                @forelse ($teamPlayers as $player)
                                    <input type="text" name="player_names[]" value="{{ old('player_names.'.$loop->index, $player->full_name ?: $player->first_name) }}" placeholder="Joueur {{ $loop->iteration }}">
                                @empty
                                    <input type="text" name="player_names[]" value="" placeholder="Joueur 1">
                                @endforelse
                            </div>

                            <div style="display:flex; gap:.65rem; flex-wrap:wrap; align-items:center;">
                                <button class="btn btn-outline" type="button" data-add-player>Ajouter joueur</button>
                                <button class="btn btn-outline" type="submit" form="deleteTeam{{ $team->id }}" style="border-color: color-mix(in srgb, var(--danger) 45%, var(--line) 55%); color: var(--danger);">Supprimer equipe</button>
                                <button class="btn btn-outline" type="submit">Enregistrer</button>
                            </div>
                        </form>

                        <form id="deleteTeam{{ $team->id }}" method="POST" action="{{ route('tournaments.teams.destroy', [$tournament, $team]) }}" onsubmit="return confirm('Retirer cette equipe ? Les joueurs seront aussi retires du tournoi.');" style="display:none;">
                            @csrf
                            @method('DELETE')
                        </form>
                    </article>
                @endforeach
            </div>

            <div style="margin-top:1rem;">
                {{ $teams->links() }}
            </div>
        @endif
    </section>
@endsection

@push('scripts')
    <script>
        (() => {
            document.addEventListener('click', (event) => {
                const button = event.target.closest('[data-add-player]');
                if (!button) return;

                const form = button.closest('[data-team-form]');
                const list = form?.querySelector('[data-player-list]');
                if (!list) return;

                const input = document.createElement('input');
                input.type = 'text';
                input.name = 'player_names[]';
                input.placeholder = `Joueur ${list.querySelectorAll('input[name="player_names[]"]').length + 1}`;

                list.appendChild(input);
                input.focus();
            });
        })();
    </script>
@endpush
