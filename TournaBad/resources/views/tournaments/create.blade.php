@extends('layouts.app')

@section('content')
    @php
        $isTeamMode = ($creationType ?? request('type')) === 'team';
        $teamMode = old('team_assignment_mode', 'random');
    @endphp

    <section class="card" style="max-width: 760px; margin: 0 auto;">
        <h1>{{ $isTeamMode ? 'Creer un tournoi en equipe' : 'Creer un tournoi' }}</h1>
        <p>
            {{ $isTeamMode
                ? 'Renseigne les informations de base, puis choisis comment preparer les equipes.'
                : 'Renseigne les informations de base du tournoi. Le tournoi sera cree en 2v2 par defaut.' }}
        </p>

        <form method="POST" action="{{ route('tournaments.store') }}">
            @csrf
            <input type="hidden" name="creation_type" value="{{ $isTeamMode ? 'team' : 'double' }}">

            <label for="name">Nom du tournoi</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required>

            <div class="grid grid-2">
                <div>
                    <label for="starts_on">Date</label>
                    <input id="starts_on" type="date" name="starts_on" value="{{ old('starts_on') }}" required>
                </div>

                <div>
                    <label for="courts_count">Nombre de terrains</label>
                    <input id="courts_count" type="number" min="1" max="20" name="courts_count" value="{{ old('courts_count', 2) }}" required>
                </div>
            </div>

            <div class="grid grid-2">
                <div>
                    <label for="round_duration_minutes">Duree d'un tour - minutes</label>
                    <input id="round_duration_minutes" type="number" min="0" max="120" name="round_duration_minutes" value="{{ old('round_duration_minutes', 12) }}" required>
                </div>

                <div>
                    <label for="round_duration_seconds">Duree d'un tour - secondes</label>
                    <input id="round_duration_seconds" type="number" min="0" max="59" name="round_duration_seconds" value="{{ old('round_duration_seconds', 0) }}" required>
                </div>
            </div>

            <label for="description">Description (optionnelle)</label>
            <input id="description" type="text" name="description" value="{{ old('description') }}" placeholder="Ex: tournoi interne du vendredi soir">

            @if ($isTeamMode)
                <section class="card" style="margin-top:1rem; background: color-mix(in srgb, var(--paper-strong) 82%, transparent 18%); box-shadow:none;">
                    <h2 style="margin-bottom:.35rem;">Equipes</h2>
                    <p style="margin-top:0;">Choisis une composition aleatoire ou des equipes fixes. Tu ajouteras ensuite les joueurs ou les binomes depuis le tournoi.</p>

                    <label for="team_assignment_mode">Composition des equipes</label>
                    <select id="team_assignment_mode" name="team_assignment_mode" data-team-mode-select>
                        <option value="random" @selected($teamMode === 'random')>Equipe aleatoire</option>
                        <option value="predefined" @selected($teamMode === 'predefined')>Equipes predefinies</option>
                    </select>

                    <div data-team-random-fields>
                        <label for="team_size">Nombre de personnes par equipe</label>
                        <input id="team_size" type="number" min="1" max="20" name="team_size" value="{{ old('team_size', 2) }}">
                    </div>

                    <div data-team-predefined-fields>
                        <p class="muted" style="margin-bottom:0;">
                            Apres creation, une page Equipes te permettra d'ajouter autant de binomes que necessaire.
                        </p>
                    </div>
                </section>
            @endif

            <div style="display:flex; gap:.7rem; flex-wrap:wrap; margin-top:1rem;">
                <button class="btn btn-primary" type="submit">Creer le tournoi</button>
                <a class="btn btn-outline" href="{{ route('tournaments.index') }}">Annuler</a>
            </div>
        </form>
    </section>
@endsection

@push('scripts')
    <script>
        (() => {
            const select = document.querySelector('[data-team-mode-select]');
            if (!select) return;

            const randomFields = document.querySelector('[data-team-random-fields]');
            const predefinedFields = document.querySelector('[data-team-predefined-fields]');

            const syncFields = () => {
                const isRandom = select.value === 'random';
                if (randomFields) randomFields.hidden = !isRandom;
                if (predefinedFields) predefinedFields.hidden = isRandom;
            };

            select.addEventListener('change', syncFields);
            syncFields();
        })();
    </script>
@endpush
