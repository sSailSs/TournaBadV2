<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\TournamentMatch;
use App\Models\Round;
use App\Models\RoundWaitingPlayer;
use App\Models\Tournament;
use App\Services\MatchScoreRecorder;
use App\Services\RoundGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TournamentController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $tournaments = Tournament::query()
            ->where('creator_id', $user->id)
            ->withCount('players')
            ->latest('starts_on')
            ->paginate(12);

        return view('tournaments.index', [
            'tournaments' => $tournaments,
        ]);
    }

    public function create(Request $request): View
    {
        return view('tournaments.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'starts_on' => ['required', 'date'],
            'courts_count' => ['required', 'integer', 'min:1', 'max:20'],
            'round_duration_minutes' => ['required', 'integer', 'min:0', 'max:120'],
            'round_duration_seconds' => ['required', 'integer', 'min:0', 'max:59'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        if ((int) $validated['round_duration_minutes'] === 0 && (int) $validated['round_duration_seconds'] === 0) {
            throw ValidationException::withMessages([
                'round_duration_seconds' => 'La duree du tour doit etre superieure a zero.',
            ]);
        }

        $roundDurationSeconds = ((int) $validated['round_duration_minutes'] * 60) + (int) $validated['round_duration_seconds'];

        $tournament = Tournament::create([
            ...$validated,
            'round_duration_seconds' => $roundDurationSeconds,
            'format' => 'double',
            'allow_2v1' => false,
            'allow_1v1' => false,
            'alarm_audio_path' => null,
            'creator_id' => $request->user()->id,
            'status' => 'draft',
        ]);

        return redirect()
            ->route('tournaments.show', $tournament)
            ->with('status', 'Tournoi cree avec succes.');
    }

    public function destroy(Request $request, Tournament $tournament): RedirectResponse
    {
        $tournament = $this->userTournament($request, $tournament);

        $tournament->delete();

        return redirect()
            ->route('tournaments.index')
            ->with('status', 'Tournoi supprime.');
    }

    public function show(Request $request, Tournament $tournament, RoundGenerator $roundGenerator): View
    {
        $tournament = $this->userTournament($request, $tournament)->loadCount('players');
        $roundDurationSeconds = $this->tournamentDurationSeconds($tournament);
        $audioOptions = $this->availableAudioOptions();

        $leaderboardPlayers = $this->buildLeaderboardPlayers($tournament);

        $challenges = $this->buildChallenges($tournament);

        $rounds = $tournament->rounds()
            ->orderByDesc('round_number')
            ->withCount(['matches', 'matches as scored_matches_count' => fn ($query) => $query->whereHas('score')])
            ->get(['id', 'round_number', 'status', 'generated_at', 'started_at', 'ended_at']);

        $currentRound = $rounds->first();
        $selectedRoundId = (int) $request->query('round', $currentRound?->id ?? 0);
        $selectedRound = $rounds->firstWhere('id', $selectedRoundId) ?? $currentRound;

        $roundPayload = $selectedRound
            ? $tournament->rounds()
                ->whereKey($selectedRound->id)
                ->with(['matches.players', 'waitingPlayers.player'])
                ->first()
            : null;

        $roundMenu = $rounds->map(function ($round) use ($currentRound, $selectedRound): array {
            $status = $this->roundDisplayStatus((int) $round->scored_matches_count, (int) $round->matches_count);

            return [
                'id' => $round->id,
                'number' => $round->round_number,
                'status_key' => $status['key'],
                'status_label' => $status['label'],
                'is_current' => $currentRound && (int) $round->id === (int) $currentRound->id,
                'is_selected' => $selectedRound && (int) $round->id === (int) $selectedRound->id,
            ];
        })->values()->all();

        $canReturnToCurrentRound = $currentRound && $selectedRound && (int) $currentRound->id !== (int) $selectedRound->id;

        $timerData = $this->buildTimerData($tournament, $currentRound, $audioOptions);

        return view('tournaments.show', [
            'tournament' => $tournament,
            'audioOptions' => $audioOptions,
            'leaderboardPlayers' => $leaderboardPlayers,
            'challenges' => $challenges,
            'roundMenu' => $roundMenu,
            'selectedRound' => $selectedRound,
            'currentRound' => $currentRound,
            'canReturnToCurrentRound' => $canReturnToCurrentRound,
            'timerData' => $timerData,
            'roundDurationSeconds' => $roundDurationSeconds,
            'roundDurationLabel' => $this->formatDuration($roundDurationSeconds),
            'latestRoundPayload' => $roundPayload ? $roundGenerator->loadRoundPayload($roundPayload) : null,
        ]);
    }

    public function settings(Request $request, Tournament $tournament): View
    {
        $tournament = $this->userTournament($request, $tournament)->loadCount('players');
        $roundDurationSeconds = $this->tournamentDurationSeconds($tournament);

        $audioOptions = $this->availableAudioOptions();
        $defaultAudioPath = $audioOptions[0]['path'] ?? null;

        if (! $tournament->alarm_audio_path && $defaultAudioPath) {
            $tournament->setAttribute('alarm_audio_path', $defaultAudioPath);
        }

        return view('tournaments.settings', [
            'tournament' => $tournament,
            'audioOptions' => $audioOptions,
            'roundDurationSeconds' => $roundDurationSeconds,
            'roundDurationLabel' => $this->formatDuration($roundDurationSeconds),
        ]);
    }

    public function points(Request $request, Tournament $tournament): View
    {
        $tournament = $this->userTournament($request, $tournament)->loadCount('players');

        [$playerStats] = $this->buildPlayerStats($tournament, true);

        return view('tournaments.points', [
            'tournament' => $tournament,
            'players' => collect($playerStats)
                ->sort(function (array $left, array $right): int {
                    if ($left['points'] === $right['points']) {
                        return strcmp($left['name'], $right['name']);
                    }

                    return $right['points'] <=> $left['points'];
                })
                ->map(fn (array $stat) => (object) [
                    'full_name' => $stat['name'],
                    'first_name' => $stat['name'],
                    'points' => $stat['points'],
                    'wins' => $stat['wins'],
                    'losses' => $stat['losses'],
                    'waiting_count' => $stat['waiting_count'] ?? 0,
                ])
                ->values()
                ->all(),
        ]);
    }

    public function final(Request $request, Tournament $tournament): View
    {
        $tournament = $this->userTournament($request, $tournament)->loadCount('players');

        [$playerStats] = $this->buildPlayerStats($tournament, true);

        $players = collect($playerStats)
            ->sort(function (array $left, array $right): int {
                if ($left['points'] === $right['points']) {
                    return strcmp($left['name'], $right['name']);
                }

                return $right['points'] <=> $left['points'];
            })
            ->map(fn (array $stat) => (object) [
                'full_name' => $stat['name'],
                'first_name' => $stat['name'],
                'points' => $stat['points'],
                'wins' => $stat['wins'],
                'losses' => $stat['losses'],
                'waiting_count' => $stat['waiting_count'] ?? 0,
            ])
            ->values();

        return view('tournaments.final', [
            'tournament' => $tournament,
            'players' => $players,
        ]);
    }

    public function reset(Request $request, Tournament $tournament): RedirectResponse
    {
        $tournament = $this->userTournament($request, $tournament);

        DB::transaction(function () use ($tournament): void {
            $tournament->matches()->delete();
            $tournament->rounds()->delete();
            $tournament->players()->update([
                'points' => 0,
            ]);
            $tournament->update([
                'status' => 'draft',
            ]);
        });

        return redirect()
            ->route('tournaments.show', $tournament)
            ->with('status', 'Tour remis a zero.');
    }

    public function updateSettings(Request $request, Tournament $tournament): RedirectResponse
    {
        $tournament = $this->userTournament($request, $tournament);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'starts_on' => ['required', 'date'],
            'courts_count' => ['required', 'integer', 'min:1', 'max:20'],
            'round_duration_minutes' => ['required', 'integer', 'min:0', 'max:120'],
            'round_duration_seconds' => ['required', 'integer', 'min:0', 'max:59'],
            'description' => ['nullable', 'string', 'max:1000'],
            'format' => ['required', 'string', 'in:double,single,mixed'],
            'allow_2v1' => ['nullable', 'boolean'],
            'allow_1v1' => ['nullable', 'boolean'],
            'alarm_audio_path' => ['nullable', 'string', 'max:255'],
        ]);

        if ((int) $validated['round_duration_minutes'] === 0 && (int) $validated['round_duration_seconds'] === 0) {
            throw ValidationException::withMessages([
                'round_duration_seconds' => 'La duree du tour doit etre superieure a zero.',
            ]);
        }

        $roundDurationSeconds = ((int) $validated['round_duration_minutes'] * 60) + (int) $validated['round_duration_seconds'];

        $format = $validated['format'];

        $allow2v1 = $request->boolean('allow_2v1');
        $allow1v1 = $request->boolean('allow_1v1');

        if ($format === 'single') {
            $allow2v1 = false;
            $allow1v1 = true;
        }

        $audioOptions = $this->availableAudioOptions();
        $availableAudioFiles = collect($audioOptions)->pluck('path')->all();
        $alarmAudioPath = $validated['alarm_audio_path'] ?? null;

        if ($alarmAudioPath !== null && ! in_array($alarmAudioPath, $availableAudioFiles, true)) {
            $alarmAudioPath = null;
        }

        if ($alarmAudioPath === null) {
            $alarmAudioPath = $audioOptions[0]['path'] ?? null;
        }

        $tournament->update([
            'name' => $validated['name'],
            'starts_on' => $validated['starts_on'],
            'courts_count' => $validated['courts_count'],
            'round_duration_minutes' => $validated['round_duration_minutes'],
            'round_duration_seconds' => $roundDurationSeconds,
            'description' => $validated['description'] ?? null,
            'format' => $format,
            'allow_2v1' => $allow2v1,
            'allow_1v1' => $allow1v1,
            'alarm_audio_path' => $alarmAudioPath,
        ]);

        return redirect()
            ->route('tournaments.show', $tournament)
            ->with('status', 'Parametres du tournoi mis a jour.');
    }

    public function generateRound(Request $request, Tournament $tournament, RoundGenerator $roundGenerator): JsonResponse|RedirectResponse
    {
        $tournament = $this->userTournament($request, $tournament);

        $payload = $roundGenerator->generate($tournament);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Tour genere avec succes.',
                'round' => $payload,
            ]);
        }

        return redirect()
            ->route('tournaments.show', $tournament)
            ->with('status', 'Tour genere avec succes.');
    }

    public function recordMatchScore(Request $request, Tournament $tournament, TournamentMatch $match, RoundGenerator $roundGenerator, MatchScoreRecorder $recorder): JsonResponse|RedirectResponse
    {
        $tournament = $this->userTournament($request, $tournament);

        if ((int) $match->tournament_id !== (int) $tournament->id) {
            abort(404);
        }

        $validated = $request->validate([
            'team_one_score' => ['required', 'integer', 'min:0'],
            'team_two_score' => ['required', 'integer', 'min:0'],
        ]);

        $recorder->record(
            $match,
            (int) $validated['team_one_score'],
            (int) $validated['team_two_score'],
            $request->user()->id,
        );

        $updatedRound = $match->round()
            ->with(['matches.players', 'matches.score', 'waitingPlayers.player'])
            ->firstOrFail();

        $payload = $roundGenerator->loadRoundPayload($updatedRound);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Score enregistre avec succes.',
                'round' => $payload,
            ]);
        }

        return redirect()
            ->route('tournaments.show', $tournament)
            ->with('status', 'Score enregistre avec succes.');
    }

    public function players(Request $request, Tournament $tournament): View
    {
        $tournament = $this->userTournament($request, $tournament);

        $players = $tournament->players()
            ->orderBy('first_name')
            ->paginate(20);

        return view('tournaments.players', [
            'tournament' => $tournament,
            'players' => $players,
        ]);
    }

    public function addPlayer(Request $request, Tournament $tournament): RedirectResponse
    {
        $tournament = $this->userTournament($request, $tournament);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
        ]);

        Player::create([
            ...$validated,
            'tournament_id' => $tournament->id,
            'created_by' => $request->user()->id,
            'is_active' => true,
        ]);

        return redirect()
            ->route('tournaments.players', $tournament)
            ->with('status', 'Joueur ajoute.');
    }

    public function removePlayer(Request $request, Tournament $tournament, Player $player): RedirectResponse
    {
        $tournament = $this->userTournament($request, $tournament);

        if ((int) $player->tournament_id !== (int) $tournament->id) {
            abort(404);
        }

        $player->delete();

        return redirect()
            ->route('tournaments.players', $tournament)
            ->with('status', 'Joueur retire.');
    }

    private function userTournament(Request $request, Tournament $tournament): Tournament
    {
        if ((int) $tournament->creator_id !== (int) $request->user()->id) {
            abort(403);
        }

        return $tournament;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildChallenges(Tournament $tournament): array
    {
        [$playerStats, $hasScoredMatches] = $this->buildPlayerStats($tournament);

        if (! $hasScoredMatches) {
            return [
                [
                    'title' => 'Défi explosif',
                    'label' => 'Tour et match du meilleur score',
                    'value' => 'Aucun score pour le moment',
                ],
                [
                    'title' => 'Défi discret',
                    'label' => 'Le moins de points',
                    'value' => 'Aucun score pour le moment',
                ],
                [
                    'title' => 'Défi victoire',
                    'label' => 'Le plus de victoires',
                    'value' => 'Aucune victoire pour le moment',
                ],
            ];
        }

        $bestMatchPoints = null;
        $bestMatchRoundNumber = null;
        $bestMatchNumber = null;

        $previousMatchesCount = 0;

        foreach ($tournament->rounds()
            ->with(['matches.score'])
            ->orderBy('round_number')
            ->get() as $round) {
            $orderedMatches = $round->matches->sortBy('court_number')->values();

            foreach ($orderedMatches as $index => $match) {
                if (! $match->score) {
                    continue;
                }

                $teamOneScore = (int) $match->score->team_one_score;
                $teamTwoScore = (int) $match->score->team_two_score;
                $currentBest = max($teamOneScore, $teamTwoScore);

                if ($bestMatchPoints !== null && $currentBest <= $bestMatchPoints) {
                    continue;
                }

                $bestMatchPoints = $currentBest;
                $bestMatchRoundNumber = (int) $round->round_number;
                $bestMatchNumber = $previousMatchesCount + $index + 1;
            }

            $previousMatchesCount += $orderedMatches->count();
        }

        $mostWinsPlayer = collect($playerStats)
            ->sortByDesc('wins')
            ->first();

        $leastPointsPlayer = collect($playerStats)
            ->sort(function (array $left, array $right): int {
                if ($left['points'] === $right['points']) {
                    return strcmp($left['name'], $right['name']);
                }

                return $left['points'] <=> $right['points'];
            })
            ->first();

        return [
            [
                'title' => 'Défi explosif',
                'label' => 'Tour et match du meilleur score',
                'value' => $bestMatchRoundNumber && $bestMatchNumber && $bestMatchPoints !== null
                    ? sprintf('Tour %d, match n°%d - %d point%s', $bestMatchRoundNumber, $bestMatchNumber, $bestMatchPoints, $bestMatchPoints > 1 ? 's' : '')
                    : 'Aucun score pour le moment',
            ],
            [
                'title' => 'Défi discret',
                'label' => 'Le moins de points',
                'value' => $leastPointsPlayer ? sprintf('%s avec %d point%s', $leastPointsPlayer['name'], $leastPointsPlayer['points'], $leastPointsPlayer['points'] > 1 ? 's' : '') : 'Aucun score pour le moment',
            ],
            [
                'title' => 'Défi victoire',
                'label' => 'Le plus de victoires',
                'value' => $mostWinsPlayer ? sprintf('%s avec %d victoire(s)', $mostWinsPlayer['name'], $mostWinsPlayer['wins']) : 'Aucune victoire pour le moment',
            ],
        ];
    }

    /**
     * @return array<int, object>
     */
    private function buildLeaderboardPlayers(Tournament $tournament): array
    {
        [$playerStats, $hasScoredMatches] = $this->buildPlayerStats($tournament);

        if ($hasScoredMatches) {
            return collect($playerStats)
                ->sort(function (array $left, array $right): int {
                    if ($left['points'] === $right['points']) {
                        return strcmp($left['name'], $right['name']);
                    }

                    return $right['points'] <=> $left['points'];
                })
                ->take(3)
                ->map(fn (array $stat) => (object) [
                    'full_name' => $stat['name'],
                    'first_name' => $stat['name'],
                    'points' => $stat['points'],
                ])
                ->values()
                ->all();
        }

        return [];
    }

    private function buildTimerData(Tournament $tournament, ?Round $currentRound, array $audioOptions): array
    {
        $audioOption = collect($this->availableAudioOptions())
            ->firstWhere('path', $tournament->alarm_audio_path)
            ?? $audioOptions[0]
            ?? null;

        if (! $currentRound) {
            return [
                'round_id' => null,
                'round_number' => null,
                'round_duration_seconds' => $this->tournamentDurationSeconds($tournament),
                'audio_path' => $audioOption['path'] ?? null,
                'audio_url' => $audioOption['url'] ?? null,
                'audio_label' => $audioOption['label'] ?? null,
            ];
        }

        return [
            'round_id' => $currentRound->id,
            'round_number' => $currentRound->round_number,
            'round_duration_seconds' => $this->tournamentDurationSeconds($tournament),
            'audio_path' => $audioOption['path'] ?? null,
            'audio_url' => $audioOption['url'] ?? null,
            'audio_label' => $audioOption['label'] ?? 'Aucun son selectionne',
        ];
    }

    private function tournamentDurationSeconds(Tournament $tournament): int
    {
        if ($tournament->round_duration_seconds !== null) {
            return (int) $tournament->round_duration_seconds;
        }

        return (int) $tournament->round_duration_minutes * 60;
    }

    private function formatDuration(int $seconds): string
    {
        $minutes = intdiv($seconds, 60);
        $remainingSeconds = $seconds % 60;

        return sprintf('%d min %02d s', $minutes, $remainingSeconds);
    }

    /**
     * @return array<int, array{path: string, url: string, label: string}>
     */
    private function availableAudioOptions(): array
    {
        $audioDirectory = public_path('audio');

        if (! File::isDirectory($audioDirectory)) {
            return [];
        }

        return collect(File::files($audioDirectory))
            ->filter(function ($file): bool {
                return in_array(strtolower($file->getExtension()), ['mp3', 'wav', 'ogg', 'm4a'], true);
            })
            ->sortBy(fn ($file) => $file->getFilename())
            ->map(fn ($file) => [
                'path' => 'audio/'.$file->getFilename(),
                'url' => asset('audio/'.$file->getFilename()),
                'label' => $file->getFilename(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{0: array<int, array<string, int|string>>, 1: bool}
     */
    private function buildPlayerStats(Tournament $tournament, bool $includeAllPlayers = false): array
    {
        $playerStats = [];

        if ($includeAllPlayers) {
            foreach ($tournament->players()->withTrashed()->get() as $player) {
                $playerStats[(int) $player->id] = [
                    'name' => $player->full_name ?: $player->first_name,
                    'points' => 0,
                    'wins' => 0,
                    'losses' => 0,
                    'waiting_count' => 0,
                ];
            }

            $waitingCounts = RoundWaitingPlayer::query()
                ->join('rounds', 'rounds.id', '=', 'round_waiting_players.round_id')
                ->where('rounds.tournament_id', $tournament->id)
                ->selectRaw('round_waiting_players.player_id, COUNT(*) as waiting_count')
                ->groupBy('round_waiting_players.player_id')
                ->pluck('waiting_count', 'player_id');

            foreach ($waitingCounts as $playerId => $waitingCount) {
                $playerId = (int) $playerId;
                $playerStats[$playerId]['waiting_count'] = (int) $waitingCount;
            }
        }

        $matches = $tournament->matches()
            ->with(['players', 'score'])
            ->get();

        $hasScoredMatches = false;

        foreach ($matches as $match) {
            if (! $match->score) {
                continue;
            }

            $hasScoredMatches = true;

            $teamOnePlayers = $match->players->where('pivot.team_number', 1);
            $teamTwoPlayers = $match->players->where('pivot.team_number', 2);

            $teamOneScore = (int) $match->score->team_one_score;
            $teamTwoScore = (int) $match->score->team_two_score;

            foreach ($teamOnePlayers as $player) {
                $playerId = (int) $player->id;
                $playerStats[$playerId] ??= [
                    'name' => $player->full_name ?: $player->first_name,
                    'points' => 0,
                    'wins' => 0,
                    'losses' => 0,
                    'waiting_count' => 0,
                ];

                $playerStats[$playerId]['points'] += $teamOneScore;

                if ($teamOneScore > $teamTwoScore) {
                    $playerStats[$playerId]['wins']++;
                } elseif ($teamOneScore < $teamTwoScore) {
                    $playerStats[$playerId]['losses']++;
                }
            }

            foreach ($teamTwoPlayers as $player) {
                $playerId = (int) $player->id;
                $playerStats[$playerId] ??= [
                    'name' => $player->full_name ?: $player->first_name,
                    'points' => 0,
                    'wins' => 0,
                    'losses' => 0,
                    'waiting_count' => 0,
                ];

                $playerStats[$playerId]['points'] += $teamTwoScore;

                if ($teamTwoScore > $teamOneScore) {
                    $playerStats[$playerId]['wins']++;
                } elseif ($teamTwoScore < $teamOneScore) {
                    $playerStats[$playerId]['losses']++;
                }
            }
        }

        return [$playerStats, $hasScoredMatches];
    }

    private function roundDisplayStatus(int $scoredMatchesCount, int $matchesCount): array
    {
        if ($matchesCount === 0 || $scoredMatchesCount === 0) {
            return [
                'key' => 'no-score',
                'label' => 'sans score',
            ];
        }

        if ($scoredMatchesCount < $matchesCount) {
            return [
                'key' => 'started',
                'label' => 'en cours',
            ];
        }

        return [
            'key' => 'completed',
            'label' => 'terminé',
        ];
    }
}
