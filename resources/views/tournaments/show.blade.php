@extends('layouts.app')

@section('content')
    <section class="card tournament-header-card">
        <div class="tournament-header-main" style="display:flex; align-items:start; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
            <div>
                <h1 style="margin-bottom:.4rem;">{{ $tournament->name }}</h1>
                <p style="margin:0;">Mode: {{ $tournament->format === 'double' ? '2 vs 2' : ($tournament->format === 'team' ? 'En equipe' : ucfirst($tournament->format)) }} | Date: {{ $tournament->starts_on?->format('d/m/Y') }} | Statut: {{ ucfirst($tournament->status) }}</p>
            </div>
            <div class="tournament-actions">
                <div class="tournament-actions-desktop">
                    <a class="btn btn-outline" href="{{ route('tournaments.settings', $tournament) }}">Parametres</a>
                    @if ($tournament->format === 'team' && $tournament->team_assignment_mode === 'predefined')
                        <a class="btn btn-outline" href="{{ route('tournaments.teams', $tournament) }}">Equipes</a>
                    @else
                        <a class="btn btn-outline" href="{{ route('tournaments.players', $tournament) }}">Joueurs</a>
                    @endif
                    <a class="btn btn-outline" href="{{ route('tournaments.points', $tournament) }}">Points</a>
                    <button class="btn btn-outline" type="button" data-final-open>Fin du tournoi</button>
                </div>

                <div class="tournament-actions-mobile">
                    <button class="btn btn-outline btn-icon" type="button" data-tournament-menu-toggle aria-expanded="false" aria-label="Ouvrir le menu du tournoi">
                        <span class="hamburger-lines" aria-hidden="true">
                            <span></span>
                            <span></span>
                            <span></span>
                        </span>
                    </button>

                    <div class="tournament-actions-menu" data-tournament-menu>
                        <a class="btn btn-outline" href="{{ route('tournaments.settings', $tournament) }}">Parametres</a>
                        @if ($tournament->format === 'team' && $tournament->team_assignment_mode === 'predefined')
                            <a class="btn btn-outline" href="{{ route('tournaments.teams', $tournament) }}">Equipes</a>
                        @else
                            <a class="btn btn-outline" href="{{ route('tournaments.players', $tournament) }}">Joueurs</a>
                        @endif
                        <a class="btn btn-outline" href="{{ route('tournaments.points', $tournament) }}">Points</a>
                        <button class="btn btn-outline" type="button" data-final-open>Fin du tournoi</button>
                    </div>
                </div>
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
            @include('tournaments._rounds_menu')
        </section>
    </aside>

    <section class="grid grid-3 tournament-summary-cards" style="margin-top:1rem;">
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

    @if ($tournament->format === 'team')
        <section class="card tournament-team-config" style="margin-top:1rem;">
            <h2 style="margin-bottom:.35rem;">Configuration des equipes</h2>
            @if ($tournament->team_assignment_mode === 'random')
                <p style="margin:0;">Composition aleatoire avec <strong>{{ $tournament->team_size }}</strong> personne(s) par equipe.</p>
            @else
                <p style="margin-top:0;">Equipes predefinies pour ce tournoi.</p>
                <div class="list">
                    @forelse ($tournament->teams()->orderBy('position')->get() as $team)
                        <span class="tag">{{ $team->team_label ?: $team->name }}</span>
                    @empty
                        <span class="tag">Aucune equipe ajoutee</span>
                    @endforelse
                </div>
                <a class="btn btn-outline" style="margin-top:1rem;" href="{{ route('tournaments.teams', $tournament) }}">Gerer les equipes</a>
            @endif
        </section>
    @endif

    <section class="card" style="margin-top:1rem;">
        <div style="display:flex; align-items:start; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
            <div>
                <h2 style="margin-bottom:.35rem;">Generation du tour</h2>
                <p style="margin:0;">Le programme repartit automatiquement les joueurs sur les terrains et met le reste en attente.</p>
            </div>

            <div style="display:flex; gap:.8rem; align-items:center; justify-content:flex-end; flex-wrap:wrap; margin-left:auto;">
                @if ($currentRound)
                    <button class="btn btn-outline" type="button" data-delete-round-open style="border-color:rgba(248,113,113,.55); color:#fecaca;">
                        Supprimer ce tour
                    </button>
                @endif

                <form id="generateRoundForm" method="POST" action="{{ route('tournaments.rounds.generate', $tournament) }}" style="display:flex; gap:.8rem; align-items:center; flex-wrap:wrap;">
                    @csrf
                    <button class="btn btn-primary" type="submit">Generer le prochain tour</button>
                </form>
            </div>
        </div>

        <div id="roundProgram" style="margin-top:1rem;" data-initial='@json($latestRoundPayload)'></div>
    </section>

    @if ($currentRound)
        <div id="deleteRoundModal"
            style="position:fixed; inset:0; z-index:70; background:rgba(6,12,24,.62); display:none; align-items:center; justify-content:center; padding:1rem;"
        >
            <div class="card" style="width:min(480px, 100%);">
                <h3 style="margin-top:0; margin-bottom:.4rem;">Supprimer le tour {{ $currentRound->round_number }} ?</h3>
                <p class="muted" style="margin:0 0 1rem;">
                    Etes-vous sur ? Les matchs, les attentes et les scores de ce tour seront supprimes.
                </p>

                <div style="display:flex; justify-content:flex-end; gap:.65rem; flex-wrap:wrap;">
                    <button class="btn btn-outline" type="button" data-delete-round-close>Non</button>
                    <form method="POST" action="{{ route('tournaments.rounds.destroy', [$tournament, $currentRound]) }}" style="margin:0;">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-outline" type="submit" style="border-color:rgba(248,113,113,.65); color:#fecaca;">
                            Oui, supprimer
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <div id="finalTournamentModal"
        style="position:fixed; inset:0; z-index:75; background:rgba(6,12,24,.62); display:none; align-items:center; justify-content:center; padding:1rem;"
    >
        <div class="card" style="width:min(520px, 100%);">
            <h3 style="margin-top:0; margin-bottom:.4rem;">Finir le tournoi ?</h3>
            <p class="muted" style="margin:0 0 1rem;">
                Souhaitez-vous finir le tournoi ? Les resultats seront affiches avec un historique. Vous pourrez toujours reprendre le tournoi par la suite.
            </p>

            <div style="display:flex; justify-content:flex-end; gap:.65rem; flex-wrap:wrap;">
                <button class="btn btn-outline" type="button" data-final-close>Annuler</button>
                <a class="btn btn-primary" href="{{ route('tournaments.final', $tournament) }}">Voir les resultats</a>
            </div>
        </div>
    </div>

    <section class="card rounds-inline" style="margin-top:1rem;" aria-label="Tours du tournoi">
        @include('tournaments._rounds_menu')
    </section>

    <aside class="mini-ranking-floating" aria-label="Mini classement">
        <section class="card">
            <h3 style="margin-bottom:.45rem;">Mini classement</h3>
            <p class="muted" style="margin-top:0; margin-bottom:.7rem;">{{ $tournament->format === 'team' && $tournament->team_assignment_mode === 'predefined' ? 'Top equipes du tournoi' : 'Top joueurs du tournoi' }}</p>

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
    <script>
        (() => {
            document.querySelectorAll('[data-tournament-menu-toggle]').forEach((button) => {
                const wrapper = button.closest('.tournament-actions-mobile');
                const menu = wrapper?.querySelector('[data-tournament-menu]');

                if (!menu) {
                    return;
                }

                const closeMenu = () => {
                    menu.classList.remove('is-open');
                    button.setAttribute('aria-expanded', 'false');
                    button.setAttribute('aria-label', 'Ouvrir le menu du tournoi');
                };

                button.addEventListener('click', () => {
                    const isOpen = menu.classList.toggle('is-open');
                    button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                    button.setAttribute('aria-label', isOpen ? 'Fermer le menu du tournoi' : 'Ouvrir le menu du tournoi');
                });

                document.addEventListener('click', (event) => {
                    if (!menu.classList.contains('is-open')) {
                        return;
                    }

                    if (button.contains(event.target) || menu.contains(event.target)) {
                        return;
                    }

                    closeMenu();
                });

                window.addEventListener('resize', () => {
                    if (window.innerWidth > 900) {
                        closeMenu();
                    }
                });
            });
        })();
    </script>

    <script>
        (() => {
            const modal = document.getElementById('finalTournamentModal');
            const openButtons = document.querySelectorAll('[data-final-open]');
            const closeButton = document.querySelector('[data-final-close]');

            if (!modal || !openButtons.length || !closeButton) {
                return;
            }

            const openModal = () => {
                modal.style.display = 'flex';
                closeButton.focus();
            };

            const closeModal = () => {
                modal.style.display = 'none';
            };

            openButtons.forEach((button) => {
                button.addEventListener('click', openModal);
            });

            closeButton.addEventListener('click', closeModal);
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

    @if ($currentRound)
        <script>
            (() => {
                const modal = document.getElementById('deleteRoundModal');
                const openButton = document.querySelector('[data-delete-round-open]');
                const closeButton = document.querySelector('[data-delete-round-close]');

                if (!modal || !openButton || !closeButton) {
                    return;
                }

                const openModal = () => {
                    modal.style.display = 'flex';
                    closeButton.focus();
                };

                const closeModal = () => {
                    modal.style.display = 'none';
                };

                openButton.addEventListener('click', openModal);
                closeButton.addEventListener('click', closeModal);
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
    @endif
    <script src="{{ asset('js/tournaments/utils.js') }}?v={{ filemtime(public_path('js/tournaments/utils.js')) }}"></script>
    <script src="{{ asset('js/tournaments/audio.js') }}?v={{ filemtime(public_path('js/tournaments/audio.js')) }}"></script>
    <script src="{{ asset('js/tournaments/timer.js') }}?v={{ filemtime(public_path('js/tournaments/timer.js')) }}"></script>
    <script src="{{ asset('js/tournaments/matches.js') }}?v={{ filemtime(public_path('js/tournaments/matches.js')) }}"></script>
    <script src="{{ asset('js/tournaments/round.js') }}?v={{ filemtime(public_path('js/tournaments/round.js')) }}"></script>
    <script src="{{ asset('js/tournaments/index.js') }}?v={{ filemtime(public_path('js/tournaments/index.js')) }}"></script>
@endpush
