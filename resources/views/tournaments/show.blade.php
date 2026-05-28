@extends('layouts.app')

@section('content')
    <section class="card">
        <div style="display:flex; align-items:start; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
            <div>
                <h1 style="margin-bottom:.4rem;">{{ $tournament->name }}</h1>
                <p style="margin:0;">Mode: {{ $tournament->format === 'double' ? '2 vs 2' : ucfirst($tournament->format) }} | Date: {{ $tournament->starts_on?->format('d/m/Y') }} | Statut: {{ ucfirst($tournament->status) }}</p>
            </div>
            <div style="display:flex; gap:.7rem; flex-wrap:wrap;">
                <a class="btn btn-outline" href="{{ route('tournaments.settings', $tournament) }}">Parametres</a>
                <a class="btn btn-outline" href="{{ route('tournaments.players', $tournament) }}">Joueurs</a>
                <a class="btn btn-outline" href="{{ route('tournaments.points', $tournament) }}">Points</a>
                <a class="btn btn-outline" href="{{ route('tournaments.final', $tournament) }}">Fin du tournoi</a>
            </div>
        </div>
    </section>

    <section class="timer-floating" aria-label="Chronometre du tour">
        <section class="card timer-card" data-timer-card
            data-round-id="{{ $timerData['round_id'] ?? '' }}"
            data-audio-url="{{ $timerData['audio_url'] ?? '' }}"
            data-audio-label="{{ $timerData['audio_label'] ?? '' }}"
            data-round-number="{{ $timerData['round_number'] ?? '' }}"
            data-round-duration="{{ $timerData['round_duration_seconds'] ?? $roundDurationSeconds }}"
            data-tournament-show-route="{{ route('tournaments.show', $tournament) }}"
            data-match-score-route="{{ url('/tournaments/'.$tournament->id.'/matches') }}"
        >
            <div style="display:flex; flex-direction:column; align-items:center; gap:.25rem; margin-bottom:.65rem; text-align:center;">
                <h3 style="margin-bottom:0;">Chrono</h3>
                <p class="muted" style="margin:0;">Temps restant pour le tour en cours</p>
            </div>

            <div class="timer-display" data-timer-display>
                {{ $timerData['round_id'] ? '00:00' : '--:--' }}
            </div>

            <div class="timer-actions">
                <button class="btn btn-primary timer-icon-btn" type="button" data-timer-start {{ $timerData['round_id'] ? '' : 'disabled' }} aria-label="Demarrer le chrono">
                    <span aria-hidden="true">▶</span>
                    <span class="sr-only">Demarrer</span>
                </button>
                <button class="btn btn-outline timer-icon-btn" type="button" data-timer-pause disabled aria-label="Mettre le chrono en pause">
                    <span aria-hidden="true">⏸</span>
                    <span class="sr-only">Pause</span>
                </button>
                <button class="btn btn-outline timer-icon-btn" type="button" data-timer-reset {{ $timerData['round_id'] ? '' : 'disabled' }} aria-label="Recommencer le chrono">
                    <span aria-hidden="true">⏹</span>
                    <span class="sr-only">Stop et recommencer</span>
                </button>
                <button class="btn btn-outline timer-icon-btn" type="button" data-timer-play {{ ($timerData['audio_url'] ?? null) ? '' : 'disabled' }} aria-label="Jouer le son">
                    <span aria-hidden="true">🔊</span>
                    <span class="sr-only">Son</span>
                </button>
                <span class="timer-audio-name muted" data-timer-audio-name>{{ $timerData['audio_label'] ?? 'Aucun son selectionne' }}</span>
            </div>

            <div class="timer-end-alert" data-timer-alert hidden>
                Temps ecoule. Fin du tour.
            </div>
        </section>
    </section>

    <aside class="rounds-floating" aria-label="Tours du tournoi">
        <section class="card">
            <div style="display:flex; align-items:start; justify-content:space-between; gap:.75rem; flex-wrap:wrap; margin-bottom:.75rem;">
                <div>
                    <h3 style="margin-bottom:.35rem;">Tours</h3>
                    <p class="muted" style="margin:0;">Ancien tours et tour courant</p>
                </div>

                @if ($canReturnToCurrentRound)
                    <a class="btn btn-outline" href="{{ route('tournaments.show', $tournament) }}">Revenir au tour en cours</a>
                @endif
            </div>

            <div class="rounds-scroll">
                <ol class="mini-ranking-list">
                    @forelse ($roundMenu as $roundItem)
                        <li class="mini-ranking-item {{ $roundItem['is_selected'] ? 'round-item-selected' : '' }} {{ $roundItem['is_current'] ? 'round-item-current' : '' }}" style="grid-template-columns: 26px minmax(0, 1fr) auto;">
                            <span class="mini-ranking-pos">{{ $roundItem['number'] }}</span>
                            <a class="mini-ranking-name" href="{{ route('tournaments.show', ['tournament' => $tournament, 'round' => $roundItem['id']]) }}" style="text-decoration:none; color:inherit;">
                                Tour {{ $roundItem['number'] }}
                                @if ($roundItem['is_current'])
                                    <span class="badge" style="margin-left:.35rem;">en cours</span>
                                @endif
                            </a>
                            <span class="round-status round-status-{{ $roundItem['status_key'] }}">{{ $roundItem['status_label'] }}</span>
                        </li>
                    @empty
                        <li class="mini-ranking-item">
                            <span class="mini-ranking-pos">-</span>
                            <span class="mini-ranking-name">Aucun tour</span>
                            <span class="mini-ranking-points">-</span>
                        </li>
                    @endforelse
                </ol>
            </div>
        </section>
    </aside>

    <section class="grid grid-3" style="margin-top:1rem;">
        <article class="card">
            <h3>Terrains</h3>
            <p><strong>{{ $tournament->courts_count }}</strong> terrain(x)</p>
        </article>

        <article class="card">
            <h3>Duree d'un tour</h3>
            <p><strong>{{ $roundDurationLabel }}</strong></p>
        </article>

        <article class="card">
            <h3>Joueurs inscrits</h3>
            <p><strong>{{ $tournament->players_count }}</strong> joueur(s)</p>
        </article>
    </section>

    <section class="card" style="margin-top:1rem;">
        <div style="display:flex; align-items:start; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
            <div>
                <h2 style="margin-bottom:.35rem;">Generation du tour</h2>
                <p style="margin:0;">Le programme repartit automatiquement les joueurs sur les terrains et met le reste en attente.</p>
            </div>

            <form id="generateRoundForm" method="POST" action="{{ route('tournaments.rounds.generate', $tournament) }}" style="display:flex; gap:.8rem; align-items:center; flex-wrap:wrap;">
                @csrf
                <button class="btn btn-primary" type="submit">Generer le prochain tour</button>
            </form>
        </div>

        <div id="roundProgram" style="margin-top:1rem;" data-initial='@json($latestRoundPayload)'></div>
    </section>

    <aside class="mini-ranking-floating" aria-label="Mini classement">
        <section class="card">
            <h3 style="margin-bottom:.45rem;">Mini classement</h3>
            <p class="muted" style="margin-top:0; margin-bottom:.7rem;">Top joueurs du tournoi</p>

            <ol class="mini-ranking-list">
                @forelse ($leaderboardPlayers as $player)
                    <li class="mini-ranking-item">
                        <span class="mini-ranking-pos">{{ $loop->iteration }}</span>
                        <span class="mini-ranking-name">{{ $player->full_name ?: $player->first_name }}</span>
                        <span class="mini-ranking-points">{{ $player->points }}</span>
                    </li>
                @empty
                    <li class="mini-ranking-item">
                        <span class="mini-ranking-pos">-</span>
                        <span class="mini-ranking-name">Aucun point enregistré</span>
                        <span class="mini-ranking-points">-</span>
                    </li>
                @endforelse
            </ol>
        </section>

        <section class="card" style="margin-top:1rem;">
            <h3 style="margin-bottom:.45rem;">Défis</h3>
            <p class="muted" style="margin-top:0; margin-bottom:.7rem;">Petits objectifs à suivre au fil des matchs</p>

            <div style="display:grid; gap:.75rem;">
                @foreach ($challenges as $challenge)
                    <article style="padding:.8rem; border:1px solid var(--line); border-radius:var(--radius-sm); background:color-mix(in srgb, var(--card) 92%, var(--accent) 8%);">
                        <div style="display:flex; align-items:start; justify-content:space-between; gap:.75rem;">
                            <div>
                                <strong style="display:block; margin-bottom:.2rem;">{{ $challenge['title'] }}</strong>
                                <span class="muted">{{ $challenge['label'] }}</span>
                            </div>
                        </div>
                        <p style="margin:.55rem 0 0;">{{ $challenge['value'] }}</p>
                    </article>
                @endforeach
            </div>
        </section>
    </aside>
@endsection

@push('scripts')
    <script src="{{ asset('js/tournaments/utils.js') }}"></script>
    <script src="{{ asset('js/tournaments/audio.js') }}"></script>
    <script src="{{ asset('js/tournaments/timer.js') }}"></script>
    <script src="{{ asset('js/tournaments/matches.js') }}"></script>
    <script src="{{ asset('js/tournaments/round.js') }}"></script>
    <script src="{{ asset('js/tournaments/index.js') }}"></script>
@endpush
