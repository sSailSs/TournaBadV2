@extends('layouts.app')

@section('content')
    <section class="card">
        <div style="display:flex; align-items:start; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
            <div>
                <h1 style="margin-bottom:.4rem;">{{ ($isTeamPoints ?? false) ? 'Points des équipes' : 'Points individuels' }}</h1>
                <p style="margin:0;">{{ $tournament->name }} | Chaque score saisi sur un match alimente les points, victoires et défaites.</p>
            </div>
            <a class="btn btn-outline" href="{{ route('tournaments.show', $tournament) }}">Retour au tournoi</a>
        </div>
    </section>

    <section class="card" style="margin-top:1rem;">
        @if ($isTeamPoints ?? false)
            <p class="muted" style="margin-top:0; margin-bottom:.75rem;">Les points sont regroupés par équipe pour éviter de compter plusieurs fois les joueurs d'une même équipe.</p>
        @else
            <p class="muted" style="margin-top:0; margin-bottom:.75rem;">Clique sur une ligne joueur pour modifier ses points manuellement.</p>
        @endif
        <table class="table points-table">
            <thead>
                <tr>
                    <th>{{ ($isTeamPoints ?? false) ? 'Équipe' : 'Joueur' }}</th>
                    <th>Points</th>
                    <th><span class="points-label-full">Victoires</span><span class="points-label-short">V</span></th>
                    <th><span class="points-label-full">Défaites</span><span class="points-label-short">D</span></th>
                    <th><span class="points-label-full">Attentes</span><span class="points-label-short">Att.</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($players as $player)
                    <tr
                        @if (! ($isTeamPoints ?? false))
                            class="player-row"
                            data-player-id="{{ $player->id }}"
                            data-player-name="{{ $player->full_name ?: $player->first_name }}"
                            data-player-points="{{ $player->points }}"
                            data-update-url="{{ route('tournaments.players.points.update', [$tournament, $player->id]) }}"
                            style="cursor:pointer;"
                        @endif
                    >
                        <td>
                            @if ($isTeamPoints ?? false)
                                <span title="{{ $player->players_label ?: ($player->full_name ?: $player->first_name) }}">
                                    {{ $player->full_name ?: $player->first_name }}
                                </span>
                            @else
                                {{ $player->full_name ?: $player->first_name }}
                            @endif
                        </td>
                        <td>
                            <strong>{{ $player->points }}</strong>
                            @if (($player->manual_points_adjustment ?? 0) !== 0)
                                <span class="badge" style="margin-left:.45rem;">
                                    Ajust. {{ $player->manual_points_adjustment > 0 ? '+' : '' }}{{ $player->manual_points_adjustment }}
                                </span>
                            @endif
                        </td>
                        <td>{{ $player->wins ?? 0 }}</td>
                        <td>{{ $player->losses ?? 0 }}</td>
                        <td>{{ $player->waiting_count ?? 0 }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">{{ ($isTeamPoints ?? false) ? 'Aucune équipe pour le moment.' : 'Aucun joueur pour le moment.' }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>

        <div id="pointsModal"
            style="position:fixed; inset:0; z-index:60; background:rgba(6,12,24,.55); display:none; align-items:center; justify-content:center; padding:1rem;"
        >
        <div class="card" style="width:min(460px, 100%);">
            <h3 style="margin-top:0; margin-bottom:.35rem;">Modifier les points</h3>
            <p id="pointsModalPlayerName" class="muted" style="margin:0 0 .9rem;"></p>

            <form id="pointsModalForm" method="POST">
                @csrf
                @method('PATCH')

                <label for="pointsModalInput" style="margin-top:0;">Nouveau total de points</label>
                <input id="pointsModalInput" name="points" type="number" min="0" step="1" required>

                <div style="display:flex; justify-content:flex-end; gap:.6rem; margin-top:1rem;">
                    <button type="button" class="btn btn-outline" id="pointsModalCancel">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const rows = document.querySelectorAll('.player-row');
            const modal = document.getElementById('pointsModal');
            const form = document.getElementById('pointsModalForm');
            const input = document.getElementById('pointsModalInput');
            const nameField = document.getElementById('pointsModalPlayerName');
            const cancelBtn = document.getElementById('pointsModalCancel');

            if (!rows.length || !modal || !form || !input || !nameField || !cancelBtn) {
                return;
            }

            const openModal = (row) => {
                form.action = row.dataset.updateUrl;
                input.value = row.dataset.playerPoints || '0';
                nameField.textContent = row.dataset.playerName || '';
                modal.style.display = 'flex';
                input.focus();
                input.select();
            };

            const closeModal = () => {
                modal.style.display = 'none';
            };

            rows.forEach((row) => {
                row.addEventListener('click', () => openModal(row));
                row.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        openModal(row);
                    }
                });
                row.tabIndex = 0;
            });

            cancelBtn.addEventListener('click', closeModal);

            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            window.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && modal.style.display !== 'none') {
                    closeModal();
                }
            });
        })();
    </script>
@endpush
