@extends('layouts.app')

@section('content')
    <section class="card">
        <div style="display:flex; align-items:start; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
            <div>
                <h1 style="margin-bottom:.4rem;">Paramètres</h1>
                <p style="margin:0;">Modifier le tournoi depuis une page dédiée, sans charger la page principale.</p>
            </div>

            <div style="display:flex; gap:.7rem; flex-wrap:wrap;">
                <a class="btn btn-outline" href="{{ route('tournaments.show', $tournament) }}">Retour au tournoi</a>
                @if ($tournament->format === 'team' && $tournament->team_assignment_mode === 'predefined')
                    <a class="btn btn-outline" href="{{ route('tournaments.teams', $tournament) }}">Équipes</a>
                @else
                    <a class="btn btn-outline" href="{{ route('tournaments.players', $tournament) }}">Joueurs</a>
                @endif
                <a class="btn btn-outline" href="{{ route('tournaments.points', $tournament) }}">Points</a>
            </div>
        </div>
    </section>

    <section class="grid grid-2" style="margin-top:1rem;">
        <article class="card">
            <h3>Aperçu</h3>
            <p><strong>{{ $tournament->players_count }}</strong> joueur(s)</p>
            <p><strong>{{ $tournament->courts_count }}</strong> terrain(s)</p>
            <p><strong>{{ $roundDurationLabel }}</strong> par tour</p>
            <p><strong>{{ $tournament->alarm_audio_path ? basename($tournament->alarm_audio_path) : 'Aucun' }}</strong> son alarme</p>
        </article>

        <article class="card">
            <h3>Actions rapides</h3>
            <p class="muted">Le reset se trouve ici pour garder la page tournoi légère.</p>
            <form method="POST" action="{{ route('tournaments.reset', $tournament) }}" onsubmit="return confirm('Reset le tournoi ? Cela supprime les tours et remet les points à zéro.');" style="margin:0;">
                @csrf
                <button class="btn btn-outline" type="submit" style="border-color: color-mix(in srgb, var(--danger) 35%, var(--line) 65%); color: var(--danger);">Reset le tournoi</button>
            </form>
        </article>
    </section>

    <section class="card" style="margin-top:1rem;">
        <h2 style="margin-bottom:.35rem;">Modifier le tournoi</h2>
        <p style="margin-top:0;">Nom, terrains, durée et mode du tournoi.</p>

        <form method="POST" action="{{ route('tournaments.settings.update', $tournament) }}" style="display:grid; gap:.85rem; max-width:720px;">
            @csrf
            @method('PATCH')

            <label>
                Nom du tournoi
                <input type="text" name="name" value="{{ old('name', $tournament->name) }}" required>
            </label>

            <label>
                Date de début
                <input type="date" name="starts_on" value="{{ old('starts_on', optional($tournament->starts_on)->format('Y-m-d')) }}" required>
            </label>

            <div class="grid grid-2">
                <label>
                    Terrains
                    <input type="number" name="courts_count" min="1" max="20" value="{{ old('courts_count', $tournament->courts_count) }}" required>
                </label>

                <label>
                    Durée d'un tour - minutes
                    <input type="number" name="round_duration_minutes" min="0" max="120" value="{{ old('round_duration_minutes', intdiv($roundDurationSeconds, 60)) }}" required>
                </label>

                <label>
                    Durée d'un tour - secondes
                    <input type="number" name="round_duration_seconds" min="0" max="59" value="{{ old('round_duration_seconds', $roundDurationSeconds % 60) }}" required>
                </label>
            </div>

            <label>
                Mode
                <select name="format" id="tournamentFormat">
                    <option value="double" @selected(old('format', $tournament->format) === 'double')>Double (2 vs 2)</option>
                    <option value="team" @selected(old('format', $tournament->format) === 'team')>En équipe</option>
                    <option value="mixed" @selected(old('format', $tournament->format) === 'mixed')>Mixte</option>
                </select>
            </label>

            <label>
                Description du tournoi
                <textarea name="description" rows="4" placeholder="Ex: tournoi entre amis, ambiance libre, objectifs du jour...">{{ old('description', $tournament->description) }}</textarea>
            </label>

            <label>
                Son d'alarme du timer
                <select name="alarm_audio_path">
                    <option value="">Aucun son</option>
                    @forelse ($audioOptions as $audioOption)
                        <option value="{{ $audioOption['path'] }}" @selected(old('alarm_audio_path', $tournament->alarm_audio_path) === $audioOption['path'])>
                            {{ $audioOption['label'] }}
                        </option>
                    @empty
                        <option value="" disabled>Aucun fichier audio dans public/audio</option>
                    @endforelse
                </select>
            </label>

            <button class="btn btn-primary" type="submit" style="width:fit-content;">Enregistrer les paramètres</button>
        </form>
    </section>
@endsection
