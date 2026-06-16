<?php

namespace App\Services;

use App\Models\Player;
use App\Models\Round;
use App\Models\RoundWaitingPlayer;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RoundGenerator
{
    public function generate(Tournament $tournament): array
    {
        return DB::transaction(function () use ($tournament) {
            // Serialize generation per tournament to avoid duplicate rounds on double requests.
            Tournament::query()
                ->whereKey($tournament->id)
                ->lockForUpdate()
                ->first();

            $latestRound = $tournament->rounds()
                ->latest('round_number')
                ->first();

            if ($latestRound && $latestRound->generated_at && $latestRound->generated_at->greaterThanOrEqualTo(now()->subSeconds(3))) {
                return $this->loadRoundPayload($latestRound);
            }

            if ((string) $tournament->format === 'team' && (string) $tournament->team_assignment_mode === 'predefined') {
                return $this->generatePredefinedTeamRound($tournament);
            }

            $players = $tournament->players()
                ->where('is_active', true)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();

            $roundNumber = (int) ($tournament->rounds()->max('round_number') ?? 0) + 1;
            $lastRoundGeneratedAt = $tournament->rounds()->max('generated_at');
            $shuffledPlayers = $this->shufflePlayers($players);

            $plan = $this->bestCourtPlan(
                $players->count(),
                (int) $tournament->courts_count,
                (bool) $tournament->allow_2v1,
                (bool) $tournament->allow_1v1,
                (string) $tournament->format,
            );
            $waitingNeeded = $players->count() - $plan['used'];

            $waitingHistory = $this->buildWaitingHistory($tournament);
            $recentPlayerIds = $this->recentPlayerIds($tournament, $lastRoundGeneratedAt ? Carbon::parse($lastRoundGeneratedAt) : null);

            [$waitingPlayers, $activePlayers] = $this->pickWaitingFair(
                $shuffledPlayers,
                $waitingNeeded,
                $waitingHistory,
                $roundNumber,
                $recentPlayerIds
            );

            $partnerHistory = $this->buildPartnerHistory($tournament);
            $opponentHistory = $this->buildOpponentHistory($tournament);
            $round = Round::create([
                'tournament_id' => $tournament->id,
                'round_number' => $roundNumber,
                'status' => 'generated',
                'generated_at' => now(),
            ]);

            $matchPayloads = [];
            $courtNumber = 1;

            $doublePlan = $this->bestDoubleMatchPlan(
                $activePlayers,
                (int) $plan['d'],
                $partnerHistory,
                $opponentHistory,
                $roundNumber
            );

            foreach ($doublePlan['matches'] as $pairing) {
                $matchPayloads[] = [
                    'court_number' => $courtNumber++,
                    'match_type' => 'double',
                    'players' => [
                        ['player' => $pairing['teamA'][0], 'team' => 1],
                        ['player' => $pairing['teamA'][1], 'team' => 1],
                        ['player' => $pairing['teamB'][0], 'team' => 2],
                        ['player' => $pairing['teamB'][1], 'team' => 2],
                    ],
                ];
            }

            $remainingActivePlayers = $doublePlan['remaining'];
            $cursor = 0;

            for ($i = 0; $i < $plan['h']; $i++) {
                $slice = $remainingActivePlayers->slice($cursor, 3)->values();
                $cursor += 3;
                $matchPayloads[] = [
                    'court_number' => $courtNumber++,
                    'match_type' => 'handicap',
                    'players' => [
                        ['player' => $slice[0], 'team' => 1],
                        ['player' => $slice[1], 'team' => 1],
                        ['player' => $slice[2], 'team' => 2],
                    ],
                ];
            }

            for ($i = 0; $i < $plan['s']; $i++) {
                $slice = $remainingActivePlayers->slice($cursor, 2)->values();
                $cursor += 2;
                $matchPayloads[] = [
                    'court_number' => $courtNumber++,
                    'match_type' => 'single',
                    'players' => [
                        ['player' => $slice[0], 'team' => 1],
                        ['player' => $slice[1], 'team' => 2],
                    ],
                ];
            }

            foreach ($matchPayloads as $payload) {
                $match = TournamentMatch::create([
                    'round_id' => $round->id,
                    'tournament_id' => $tournament->id,
                    'court_number' => $payload['court_number'],
                    'match_type' => $payload['match_type'],
                    'status' => 'pending',
                ]);

                foreach ($payload['players'] as $entry) {
                    $match->players()->attach($entry['player']->id, [
                        'team_number' => $entry['team'],
                    ]);
                }
            }

            foreach ($waitingPlayers as $player) {
                RoundWaitingPlayer::create([
                    'round_id' => $round->id,
                    'player_id' => $player->id,
                ]);
            }

            return $this->loadRoundPayload($round->fresh(['matches.players', 'matches.score', 'waitingPlayers.player']));
        });
    }

    public function loadRoundPayload(Round $round): array
    {
        $round->load(['matches.players', 'matches.score', 'waitingPlayers.player']);
        $tournament = $round->tournament()->with('teams.players')->first();
        $hasPredefinedTeams = $tournament
            && (string) $tournament->format === 'team'
            && (string) $tournament->team_assignment_mode === 'predefined';
        $teamsById = $hasPredefinedTeams
            ? $tournament->teams
                ->filter(fn ($team) => $this->teamPlayers($team)->count() >= 2)
                ->mapWithKeys(fn ($team) => [
                    (int) $team->id => $team,
                ])
                ->all()
            : [];

        $previousMatchesCount = TournamentMatch::query()
            ->join('rounds', 'rounds.id', '=', 'tournament_matches.round_id')
            ->where('tournament_matches.tournament_id', $round->tournament_id)
            ->where('rounds.round_number', '<', $round->round_number)
            ->count();

        return [
            'id' => $round->id,
            'round_number' => $round->round_number,
            'status' => $round->status,
            'generated_at' => $round->generated_at?->toDateTimeString(),
            'matches' => $round->matches
                ->sortBy('court_number')
                ->values()
                ->map(function (TournamentMatch $match, int $index) use ($previousMatchesCount, $hasPredefinedTeams, $teamsById) {
                    $teamAPlayers = $match->players->where('pivot.team_number', 1)->values();
                    $teamBPlayers = $match->players->where('pivot.team_number', 2)->values();
                    $teamAPlayerNames = $this->playerNames($teamAPlayers);
                    $teamBPlayerNames = $this->playerNames($teamBPlayers);
                    $teamADisplay = $hasPredefinedTeams ? $this->displayTeam($teamAPlayers, $teamsById) : null;
                    $teamBDisplay = $hasPredefinedTeams ? $this->displayTeam($teamBPlayers, $teamsById) : null;

                    $payload = [
                        'id' => $match->id,
                        'match_number' => $previousMatchesCount + $index + 1,
                        'court_number' => $match->court_number,
                        'match_type' => $match->match_type,
                        'status' => $match->status,
                        'score' => $match->score ? [
                            'team_one_score' => $match->score->team_one_score,
                            'team_two_score' => $match->score->team_two_score,
                        ] : null,
                        'team_a' => $teamADisplay ? [$teamADisplay['label']] : $teamAPlayerNames,
                        'team_b' => $teamBDisplay ? [$teamBDisplay['label']] : $teamBPlayerNames,
                    ];

                    if ($teamADisplay && $teamBDisplay) {
                        $payload['team_a_display'] = $teamADisplay;
                        $payload['team_b_display'] = $teamBDisplay;
                    }

                    return $payload;
                })->all(),
            'waiting' => $this->displayWaitingPlayers($round->waitingPlayers, $hasPredefinedTeams, $teamsById),
        ];
    }

    private function playerNames(Collection $players): array
    {
        return $players
            ->map(fn (Player $player) => $player->full_name ?: $player->first_name)
            ->values()
            ->all();
    }

    private function displayTeam(Collection $players, array $teamsById): array
    {
        $players = $players->values();
        $playerNames = $this->playerNames($players);

        if ($players->count() === 2) {
            $team = $this->findTeamForPlayers($players, $teamsById);

            if ($team) {
                $teamPlayerNames = $this->playerNames($this->teamPlayers($team));

                return [
                    'label' => $team->team_label ?: implode(' / ', $playerNames),
                    'title' => $team->team_label ? implode(' / ', $teamPlayerNames) : null,
                ];
            }
        }

        return [
            'label' => implode(' / ', $playerNames),
            'title' => null,
        ];
    }

    private function displayWaitingPlayers(Collection $waitingPlayers, bool $hasPredefinedTeams, array $teamsById): array
    {
        if (! $hasPredefinedTeams) {
            return $waitingPlayers->map(fn (RoundWaitingPlayer $waitingPlayer) => [
                'id' => $waitingPlayer->player->id,
                'name' => $waitingPlayer->player->full_name ?: $waitingPlayer->player->first_name,
            ])->values()->all();
        }

        $waitingByPlayerId = $waitingPlayers
            ->mapWithKeys(fn (RoundWaitingPlayer $waitingPlayer) => [(int) $waitingPlayer->player_id => $waitingPlayer])
            ->all();
        $displayedPlayerIds = [];
        $waiting = [];

        foreach ($teamsById as $team) {
            $teamPlayers = $this->teamPlayers($team);

            if ($teamPlayers->count() < 2 || $teamPlayers->contains(fn (Player $player) => ! isset($waitingByPlayerId[(int) $player->id]))) {
                continue;
            }

            $teamPlayerNames = $teamPlayers
                ->map(fn (Player $player) => $player->full_name ?: $player->first_name)
                ->values()
                ->all();

            $waiting[] = [
                'id' => $team->id,
                'name' => $team->team_label ?: implode(' / ', $teamPlayerNames),
                'title' => $team->team_label ? implode(' / ', $teamPlayerNames) : null,
            ];
            foreach ($teamPlayers as $player) {
                $displayedPlayerIds[] = (int) $player->id;
            }
        }

        foreach ($waitingPlayers as $waitingPlayer) {
            if (in_array((int) $waitingPlayer->player_id, $displayedPlayerIds, true)) {
                continue;
            }

            $waiting[] = [
                'id' => $waitingPlayer->player->id,
                'name' => $waitingPlayer->player->full_name ?: $waitingPlayer->player->first_name,
            ];
        }

        return $waiting;
    }

    private function teamPlayers($team): Collection
    {
        if ($team->relationLoaded('players')) {
            return $team->players->values();
        }

        return $team->players()->get()->values();
    }

    private function teamDisplayName($team): string
    {
        return $this->teamPlayers($team)
            ->map(fn (Player $player) => $player->full_name ?: $player->first_name)
            ->implode(' / ') ?: 'Equipe incomplete';
    }

    private function findTeamForPlayers(Collection $players, array $teamsById)
    {
        $playerIds = $players->pluck('id')->map(fn ($id) => (int) $id)->values()->all();

        foreach ($teamsById as $team) {
            $teamPlayerIds = $this->teamPlayers($team)->pluck('id')->map(fn ($id) => (int) $id)->all();
            $matchedIds = array_intersect($playerIds, $teamPlayerIds);

            if (count($matchedIds) === count($playerIds)) {
                return $team;
            }
        }

        return null;
    }

    private function shufflePlayers(Collection $players): Collection
    {
        return collect($players->shuffle()->values());
    }

    private function generatePredefinedTeamRound(Tournament $tournament): array
    {
        $teams = $tournament->teams()
            ->with('players')
            ->orderBy('position')
            ->get()
            ->filter(fn ($team) => $this->teamPlayers($team)->count() >= 2)
            ->values();

        $roundNumber = (int) ($tournament->rounds()->max('round_number') ?? 0) + 1;
        $teamPlan = $this->bestTeamMatchPlan(
            $teams,
            (int) $tournament->courts_count,
            $this->buildTeamOpponentHistory($tournament),
            $roundNumber
        );

        $round = Round::create([
            'tournament_id' => $tournament->id,
            'round_number' => $roundNumber,
            'status' => 'generated',
            'generated_at' => now(),
        ]);

        $courtNumber = 1;

        foreach ($teamPlan['matches'] as $plannedMatch) {
            $teamOne = $plannedMatch[0];
            $teamTwo = $plannedMatch[1];
            $match = TournamentMatch::create([
                'round_id' => $round->id,
                'tournament_id' => $tournament->id,
                'court_number' => $courtNumber++,
                'match_type' => 'team_double',
                'status' => 'pending',
            ]);

            foreach ($this->playingTeamPlayers($teamOne) as $player) {
                $match->players()->attach($player->id, [
                    'team_number' => 1,
                ]);
            }

            foreach ($this->playingTeamPlayers($teamTwo) as $player) {
                $match->players()->attach($player->id, [
                    'team_number' => 2,
                ]);
            }
        }

        foreach ($teamPlan['waiting'] as $team) {
            foreach ($this->teamPlayers($team) as $player) {
                RoundWaitingPlayer::create([
                    'round_id' => $round->id,
                    'player_id' => $player->id,
                ]);
            }
        }

        return $this->loadRoundPayload($round->fresh(['matches.players', 'matches.score', 'waitingPlayers.player']));
    }

    private function playingTeamPlayers($team): Collection
    {
        return $this->teamPlayers($team)
            ->shuffle()
            ->take(2)
            ->values();
    }

    private function buildTeamOpponentHistory(Tournament $tournament): array
    {
        $teamsByPairKey = $tournament->teams()
            ->with('players')
            ->get()
            ->filter(fn ($team) => $this->teamPlayers($team)->count() >= 2)
            ->mapWithKeys(fn ($team) => [
                (int) $team->id => $team,
            ])
            ->all();

        $history = [];

        $matches = TournamentMatch::query()
            ->where('tournament_id', $tournament->id)
            ->with(['round', 'players'])
            ->get();

        foreach ($matches as $match) {
            $teamOnePlayers = $match->players
                ->filter(fn (Player $player) => (int) $player->pivot->team_number === 1)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values();
            $teamTwoPlayers = $match->players
                ->filter(fn (Player $player) => (int) $player->pivot->team_number === 2)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values();

            if ($teamOnePlayers->count() !== 2 || $teamTwoPlayers->count() !== 2) {
                continue;
            }

            $teamOne = $this->findTeamForPlayers($teamOnePlayers->map(fn (int $playerId) => (object) ['id' => $playerId]), $teamsByPairKey);
            $teamTwo = $this->findTeamForPlayers($teamTwoPlayers->map(fn (int $playerId) => (object) ['id' => $playerId]), $teamsByPairKey);

            if (! $teamOne || ! $teamTwo) {
                continue;
            }

            $key = $this->pairKey((int) $teamOne->id, (int) $teamTwo->id);
            $roundNumber = (int) ($match->round?->round_number ?? 0);

            $history[$key] = [
                'count' => (($history[$key]['count'] ?? 0) + 1),
                'last_round' => max($history[$key]['last_round'] ?? 0, $roundNumber),
            ];
        }

        return $history;
    }

    private function bestTeamMatchPlan(Collection $teams, int $maxCourts, array $opponentHistory, int $roundNumber): array
    {
        $roundRobinPlan = $this->bestRoundRobinTeamPlan($teams->values(), $maxCourts, $opponentHistory, $roundNumber);

        if ($roundRobinPlan !== null) {
            return $roundRobinPlan;
        }

        $availableTeams = $teams->shuffle()->values();
        $targetMatchCount = min($maxCourts, intdiv($availableTeams->count(), 2));
        $best = $this->bestPairPlan(
            $availableTeams,
            $targetMatchCount,
            fn ($teamOne, $teamTwo): int => $this->teamOpponentScore($teamOne, $teamTwo, $opponentHistory, $roundNumber)
        );

        $matches = $best['matches'];
        $usedTeamIds = collect($matches)
            ->flatMap(fn (array $match) => [(int) $match[0]->id, (int) $match[1]->id])
            ->all();

        $waiting = $availableTeams
            ->reject(fn ($team) => in_array((int) $team->id, $usedTeamIds, true))
            ->values();

        return [
            'matches' => $matches,
            'waiting' => $waiting,
        ];
    }

    private function bestRoundRobinTeamPlan(Collection $teams, int $maxCourts, array $opponentHistory, int $roundNumber): ?array
    {
        if ($teams->count() < 2 || $maxCourts < intdiv($teams->count(), 2)) {
            return null;
        }

        $entries = $teams->values()->all();

        if (count($entries) % 2 === 1) {
            $entries[] = null;
        }

        $roundCount = count($entries) - 1;
        $half = intdiv(count($entries), 2);
        $roundPlans = [];

        for ($roundIndex = 0; $roundIndex < $roundCount; $roundIndex++) {
            $matches = [];
            $waiting = collect();

            for ($pairIndex = 0; $pairIndex < $half; $pairIndex++) {
                $teamOne = $entries[$pairIndex];
                $teamTwo = $entries[count($entries) - 1 - $pairIndex];

                if ($teamOne === null || $teamTwo === null) {
                    $waiting->push($teamOne ?? $teamTwo);

                    continue;
                }

                $matches[] = [$teamOne, $teamTwo];
            }

            $score = collect($matches)
                ->sum(fn (array $match): int => $this->teamOpponentScore($match[0], $match[1], $opponentHistory, $roundNumber));

            $roundPlans[] = [
                'matches' => $matches,
                'waiting' => $waiting->filter()->values(),
                'score' => $score,
                'round_index' => $roundIndex,
            ];

            $fixed = $entries[0];
            $rotating = array_slice($entries, 1);
            array_unshift($rotating, array_pop($rotating));
            $entries = array_merge([$fixed], $rotating);
        }

        usort($roundPlans, function (array $left, array $right) use ($roundCount, $roundNumber): int {
            if ($left['score'] !== $right['score']) {
                return $left['score'] <=> $right['score'];
            }

            $preferredIndex = ($roundNumber - 1) % $roundCount;
            $leftDistance = ($left['round_index'] - $preferredIndex + $roundCount) % $roundCount;
            $rightDistance = ($right['round_index'] - $preferredIndex + $roundCount) % $roundCount;

            return $leftDistance <=> $rightDistance;
        });

        return [
            'matches' => collect($roundPlans[0]['matches'])->take($maxCourts)->values()->all(),
            'waiting' => $roundPlans[0]['waiting'],
        ];
    }

    private function teamOpponentScore($teamOne, $teamTwo, array $opponentHistory, int $roundNumber): int
    {
        $key = $this->pairKey((int) $teamOne->id, (int) $teamTwo->id);
        $stats = $opponentHistory[$key] ?? [
            'count' => 0,
            'last_round' => 0,
        ];
        $justPlayed = (int) $stats['last_round'] === $roundNumber - 1;

        return ((int) $stats['count'] * 100000) + ($justPlayed ? 50000 : 0) + (int) $stats['last_round'];
    }

    private function bestPairPlan(Collection $items, int $targetMatchCount, callable $pairScore): array
    {
        if ($targetMatchCount <= 0) {
            return [
                'matches' => [],
                'score' => 0,
            ];
        }

        $best = null;
        $visited = 0;
        $visitLimit = $items->count() <= 12 ? 500000 : 50000;

        $search = function (Collection $remaining, array $matches, int $score) use (&$search, &$best, &$visited, $visitLimit, $targetMatchCount, $pairScore): void {
            if ($visited++ >= $visitLimit) {
                return;
            }

            $neededMatches = $targetMatchCount - count($matches);

            if ($neededMatches === 0) {
                if ($best === null || $score < $best['score']) {
                    $best = [
                        'matches' => $matches,
                        'score' => $score,
                    ];
                }

                return;
            }

            if (intdiv($remaining->count(), 2) < $neededMatches) {
                return;
            }

            if ($best !== null && $score >= $best['score']) {
                return;
            }

            $first = $remaining->first();
            $rest = $remaining->slice(1)->values();

            if ($remaining->count() > $neededMatches * 2) {
                $search($rest, $matches, $score);
            }

            $branches = [];
            foreach ($rest as $index => $candidate) {
                $branches[] = [
                    'index' => $index,
                    'item' => $candidate,
                    'score' => $pairScore($first, $candidate),
                    'tie' => random_int(1, PHP_INT_MAX),
                ];
            }

            usort($branches, function (array $left, array $right): int {
                if ($left['score'] !== $right['score']) {
                    return $left['score'] <=> $right['score'];
                }

                return $left['tie'] <=> $right['tie'];
            });

            foreach ($branches as $branch) {
                $nextRemaining = $rest
                    ->reject(fn ($item, int $index): bool => $index === $branch['index'])
                    ->values();

                $search(
                    $nextRemaining,
                    [...$matches, [$first, $branch['item']]],
                    $score + (int) $branch['score']
                );
            }
        };

        $search($items->values(), [], 0);

        return $best ?? [
            'matches' => [],
            'score' => PHP_INT_MAX,
        ];
    }

    private function buildWaitingHistory(Tournament $tournament): array
    {
        return RoundWaitingPlayer::query()
            ->join('rounds', 'rounds.id', '=', 'round_waiting_players.round_id')
            ->where('rounds.tournament_id', $tournament->id)
            ->selectRaw('round_waiting_players.player_id, COUNT(*) as wait_count, MAX(rounds.round_number) as last_round')
            ->groupBy('round_waiting_players.player_id')
            ->get()
            ->mapWithKeys(function ($row): array {
                return [
                    (int) $row->player_id => [
                        'count' => (int) $row->wait_count,
                        'last_round' => (int) $row->last_round,
                    ],
                ];
            })
            ->all();
    }

    private function buildOpponentHistory(Tournament $tournament): array
    {
        $history = [];

        $matches = TournamentMatch::query()
            ->where('tournament_id', $tournament->id)
            ->with(['round', 'players'])
            ->get();

        foreach ($matches as $match) {
            $roundNumber = (int) ($match->round?->round_number ?? 0);
            $teamOne = $match->players->filter(fn (Player $player) => (int) $player->pivot->team_number === 1)->values();
            $teamTwo = $match->players->filter(fn (Player $player) => (int) $player->pivot->team_number === 2)->values();

            foreach ($teamOne as $teamOnePlayer) {
                foreach ($teamTwo as $teamTwoPlayer) {
                    $key = $this->pairKey((int) $teamOnePlayer->id, (int) $teamTwoPlayer->id);
                    $history[$key] = [
                        'count' => (($history[$key]['count'] ?? 0) + 1),
                        'last_round' => max($history[$key]['last_round'] ?? 0, $roundNumber),
                    ];
                }
            }
        }

        return $history;
    }

    private function buildPartnerHistory(Tournament $tournament): array
    {
        $history = [];

        $matches = TournamentMatch::query()
            ->where('tournament_id', $tournament->id)
            ->with(['round', 'players'])
            ->get();

        foreach ($matches as $match) {
            $roundNumber = (int) ($match->round?->round_number ?? 0);
            $teamOne = $match->players->filter(fn (Player $player) => (int) $player->pivot->team_number === 1)->values();
            $teamTwo = $match->players->filter(fn (Player $player) => (int) $player->pivot->team_number === 2)->values();

            foreach ([$teamOne, $teamTwo] as $team) {
                if ($team->count() < 2) {
                    continue;
                }

                for ($i = 0; $i < $team->count(); $i++) {
                    for ($j = $i + 1; $j < $team->count(); $j++) {
                        $key = $this->pairKey((int) $team[$i]->id, (int) $team[$j]->id);
                        $history[$key] = [
                            'count' => (($history[$key]['count'] ?? 0) + 1),
                            'last_round' => max($history[$key]['last_round'] ?? 0, $roundNumber),
                        ];
                    }
                }
            }
        }

        return $history;
    }

    private function bestCourtPlan(int $nPlayers, int $maxCourts, bool $allow2v1, bool $allow1v1, string $format): array
    {
        $best = ['d' => 0, 'h' => 0, 's' => 0, 'used' => 0];

        for ($d = 0; $d <= $maxCourts; $d++) {
            for ($h = 0; $h <= $maxCourts - $d; $h++) {
                for ($s = 0; $s <= $maxCourts - $d - $h; $s++) {
                    if (! $allow2v1 && $h > 0) {
                        continue;
                    }

                    if (! $allow1v1 && $s > 0) {
                        continue;
                    }

                    if ($format === 'single' && ($d > 0 || $h > 0)) {
                        continue;
                    }

                    if ($format === 'double' && ! $allow1v1 && $s > 0) {
                        continue;
                    }

                    $used = 4 * $d + 3 * $h + 2 * $s;
                    $courts = $d + $h + $s;

                    if ($courts > $maxCourts || $used > $nPlayers) {
                        continue;
                    }

                    $better =
                        $used > $best['used'] ||
                        ($used === $best['used'] && $d > $best['d']) ||
                        ($used === $best['used'] && $d === $best['d'] && $s > $best['s']) ||
                        ($used === $best['used'] && $d === $best['d'] && $s === $best['s'] && $h < $best['h']);

                    if ($better) {
                        $best = ['d' => $d, 'h' => $h, 's' => $s, 'used' => $used];
                    }
                }
            }
        }

        return $best;
    }

    private function pickWaitingFair(Collection $allPlayersShuffled, int $waitingNeeded, array $waitingHistory, int $roundNumber, array $recentPlayerIds): array
    {
        if ($waitingNeeded <= 0) {
            return [collect(), $allPlayersShuffled->values()];
        }

        $candidates = $allPlayersShuffled
            ->values()
            ->map(function (Player $player): array {
                return [
                    'player' => $player,
                    'count' => 0,
                    'last_round' => 0,
                    'tie' => random_int(1, PHP_INT_MAX),
                ];
            })
            ->map(function (array $candidate) use ($waitingHistory): array {
                $stats = $waitingHistory[$candidate['player']->id] ?? [
                    'count' => 0,
                    'last_round' => 0,
                ];

                $candidate['count'] = (int) $stats['count'];
                $candidate['last_round'] = (int) $stats['last_round'];

                return $candidate;
            })
            ->map(function (array $candidate) use ($recentPlayerIds): array {
                $candidate['recent'] = in_array((int) $candidate['player']->id, $recentPlayerIds, true);

                return $candidate;
            })
            ->sort(function (array $left, array $right) use ($roundNumber): int {
                if ($left['count'] !== $right['count']) {
                    return $left['count'] <=> $right['count'];
                }

                if ($left['recent'] !== $right['recent']) {
                    return $left['recent'] <=> $right['recent'];
                }

                $leftRecent = $left['last_round'] === $roundNumber - 1;
                $rightRecent = $right['last_round'] === $roundNumber - 1;

                if ($leftRecent !== $rightRecent) {
                    return $leftRecent <=> $rightRecent;
                }

                if ($left['last_round'] !== $right['last_round']) {
                    return $left['last_round'] <=> $right['last_round'];
                }

                return $left['tie'] <=> $right['tie'];
            })
            ->values();

        $waitingCandidates = collect();
        $lowestCount = $candidates->first()['count'] ?? 0;
        $lowestBucket = $candidates->filter(fn (array $candidate) => $candidate['count'] === $lowestCount)->values();
        $remainingBuckets = $candidates->reject(fn (array $candidate) => $candidate['count'] === $lowestCount)->values();

        $eligibleLowestBucket = $lowestBucket->reject(fn (array $candidate) => $candidate['recent'] ?? false)->values();
        $recentLowestBucket = $lowestBucket->filter(fn (array $candidate) => $candidate['recent'] ?? false)->values();

        if ($eligibleLowestBucket->count() > 1 && $eligibleLowestBucket->count() > $waitingNeeded) {
            $offset = ($roundNumber - 1) % $eligibleLowestBucket->count();
            $eligibleLowestBucket = $eligibleLowestBucket->slice($offset)->concat($eligibleLowestBucket->slice(0, $offset))->values();
        }

        $waitingCandidates = $waitingCandidates->concat($eligibleLowestBucket->take($waitingNeeded));

        if ($waitingCandidates->count() < $waitingNeeded && $remainingBuckets->isNotEmpty()) {
            $waitingCandidates = $waitingCandidates->concat($remainingBuckets->take($waitingNeeded - $waitingCandidates->count()));
        }

        if ($waitingCandidates->count() < $waitingNeeded && $recentLowestBucket->isNotEmpty()) {
            $waitingCandidates = $waitingCandidates->concat($recentLowestBucket->take($waitingNeeded - $waitingCandidates->count()));
        }

        $waiting = $waitingCandidates
            ->map(fn (array $candidate) => $candidate['player'])
            ->values();

        $waitingIds = $waiting->pluck('id')->all();
        $activePool = $allPlayersShuffled->reject(fn (Player $player) => in_array($player->id, $waitingIds, true))->values();

        return [$waiting, $activePool];
    }

    /**
     * @return array<int>
     */
    private function recentPlayerIds(Tournament $tournament, ?\DateTimeInterface $lastRoundGeneratedAt): array
    {
        if (! $lastRoundGeneratedAt) {
            return [];
        }

        return $tournament->players()
            ->where('created_at', '>', $lastRoundGeneratedAt)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function bestDoubleMatchPlan(Collection $players, int $matchCount, array $partnerHistory, array $opponentHistory, int $roundNumber): array
    {
        if ($matchCount <= 0) {
            return [
                'matches' => [],
                'remaining' => $players->values(),
                'score' => 0,
            ];
        }

        $targetPairCount = $matchCount * 2;
        $partnerPlan = $this->bestPairPlan(
            $players->shuffle()->values(),
            $targetPairCount,
            fn (Player $playerOne, Player $playerTwo): int => $this->partnerScore($playerOne, $playerTwo, $partnerHistory, $roundNumber)
        );

        $partnerPairs = collect($partnerPlan['matches']);
        $usedPlayerIds = $partnerPairs
            ->flatten(1)
            ->map(fn (Player $player) => (int) $player->id)
            ->all();

        $matchPlan = $this->bestPairPlan(
            $partnerPairs->shuffle()->values(),
            $matchCount,
            fn (array $teamA, array $teamB): int => $this->opponentScore($teamA, $teamB, $opponentHistory, $roundNumber)
        );

        $matches = collect($matchPlan['matches'])
            ->map(fn (array $match): array => [
                'teamA' => $match[0],
                'teamB' => $match[1],
                'score' => (int) $matchPlan['score'],
            ])
            ->values()
            ->all();

        $remaining = $players
            ->reject(fn (Player $player): bool => in_array((int) $player->id, $usedPlayerIds, true))
            ->values();

        return [
            'matches' => $matches,
            'remaining' => $remaining,
            'score' => (int) $partnerPlan['score'] + (int) $matchPlan['score'],
        ];
    }

    private function bestDoublePairing(Collection $players, array $partnerHistory, array $opponentHistory, int $roundNumber): array
    {
        $players = $players->values();
        $options = [
            [[$players[0], $players[1]], [$players[2], $players[3]]],
            [[$players[0], $players[2]], [$players[1], $players[3]]],
            [[$players[0], $players[3]], [$players[1], $players[2]]],
        ];

        $bestOptions = [];
        $bestScore = null;

        foreach ($options as $option) {
            $score = $this->doublePairingScore($option[0], $option[1], $partnerHistory, $opponentHistory, $roundNumber);

            if ($bestScore === null || $score < $bestScore) {
                $bestScore = $score;
                $bestOptions = [$option];
            } elseif ($score === $bestScore) {
                $bestOptions[] = $option;
            }
        }

        $choice = $bestOptions[array_rand($bestOptions)];

        return [
            'teamA' => $choice[0],
            'teamB' => $choice[1],
            'score' => $bestScore ?? 0,
        ];
    }

    private function doublePairingScore(array $teamA, array $teamB, array $partnerHistory, array $opponentHistory, int $roundNumber): int
    {
        $score = 0;

        foreach ([$teamA, $teamB] as $pair) {
            $key = $this->pairKey((int) $pair[0]->id, (int) $pair[1]->id);
            $pairStats = $partnerHistory[$key] ?? [
                'count' => 0,
                'last_round' => 0,
            ];
            $justPlayedTogether = (int) $pairStats['last_round'] === $roundNumber - 1;

            $score += ((int) $pairStats['count'] * 10000) + ($justPlayedTogether ? 5000 : 0) + (int) $pairStats['last_round'];
        }

        foreach ($teamA as $teamAPlayer) {
            foreach ($teamB as $teamBPlayer) {
                $key = $this->pairKey((int) $teamAPlayer->id, (int) $teamBPlayer->id);
                $opponentStats = $opponentHistory[$key] ?? [
                    'count' => 0,
                    'last_round' => 0,
                ];
                $justPlayedAgainst = (int) $opponentStats['last_round'] === $roundNumber - 1;

                $score += ((int) $opponentStats['count'] * 1000) + ($justPlayedAgainst ? 2500 : 0) + (int) $opponentStats['last_round'];
            }
        }

        return $score;
    }

    private function partnerScore(Player $playerOne, Player $playerTwo, array $partnerHistory, int $roundNumber): int
    {
        $key = $this->pairKey((int) $playerOne->id, (int) $playerTwo->id);
        $stats = $partnerHistory[$key] ?? [
            'count' => 0,
            'last_round' => 0,
        ];
        $justPlayedTogether = (int) $stats['last_round'] === $roundNumber - 1;

        return ((int) $stats['count'] * 1000000) + ($justPlayedTogether ? 500000 : 0) + (int) $stats['last_round'];
    }

    private function opponentScore(array $teamA, array $teamB, array $opponentHistory, int $roundNumber): int
    {
        $score = 0;

        foreach ($teamA as $teamAPlayer) {
            foreach ($teamB as $teamBPlayer) {
                $key = $this->pairKey((int) $teamAPlayer->id, (int) $teamBPlayer->id);
                $stats = $opponentHistory[$key] ?? [
                    'count' => 0,
                    'last_round' => 0,
                ];
                $justPlayedAgainst = (int) $stats['last_round'] === $roundNumber - 1;

                $score += ((int) $stats['count'] * 10000) + ($justPlayedAgainst ? 5000 : 0) + (int) $stats['last_round'];
            }
        }

        return $score;
    }

    private function pairKey(int $a, int $b): string
    {
        $ordered = [$a, $b];
        sort($ordered, SORT_NUMERIC);

        return $ordered[0].':'.$ordered[1];
    }
}
