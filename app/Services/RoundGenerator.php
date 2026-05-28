<?php

namespace App\Services;

use App\Models\Player;
use App\Models\Round;
use App\Models\RoundWaitingPlayer;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RoundGenerator
{
    public function generate(Tournament $tournament): array
    {
        return DB::transaction(function () use ($tournament) {
            Tournament::query()
                ->whereKey($tournament->id)
                ->lockForUpdate()
                ->first();

            $latestRound = $tournament->rounds()
                ->orderByDesc('round_number')
                ->first();

            if ($latestRound && $latestRound->generated_at && $latestRound->generated_at->greaterThanOrEqualTo(now()->subSeconds(2))) {
                return $this->loadRoundPayload($latestRound->fresh(['matches.players', 'matches.score', 'waitingPlayers.player']));
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
            $round = Round::create([
                'tournament_id' => $tournament->id,
                'round_number' => $roundNumber,
                'status' => 'generated',
                'generated_at' => now(),
            ]);

            $matchPayloads = [];
            $courtNumber = 1;
            $cursor = 0;

            for ($i = 0; $i < $plan['d']; $i++) {
                $slice = $activePlayers->slice($cursor, 4)->values();
                $cursor += 4;
                $pairing = $this->bestDoublePairing($slice, $partnerHistory);
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

            for ($i = 0; $i < $plan['h']; $i++) {
                $slice = $activePlayers->slice($cursor, 3)->values();
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
                $slice = $activePlayers->slice($cursor, 2)->values();
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
                ->map(function (TournamentMatch $match, int $index) use ($previousMatchesCount) {
                return [
                    'id' => $match->id,
                    'match_number' => $previousMatchesCount + $index + 1,
                    'court_number' => $match->court_number,
                    'match_type' => $match->match_type,
                    'status' => $match->status,
                    'score' => $match->score ? [
                        'team_one_score' => $match->score->team_one_score,
                        'team_two_score' => $match->score->team_two_score,
                    ] : null,
                    'team_a' => $match->players->where('pivot.team_number', 1)->map(fn (Player $player) => $player->full_name ?: $player->first_name)->values()->all(),
                    'team_b' => $match->players->where('pivot.team_number', 2)->map(fn (Player $player) => $player->full_name ?: $player->first_name)->values()->all(),
                ];
                })->all(),
            'waiting' => $round->waitingPlayers->map(fn (RoundWaitingPlayer $waitingPlayer) => [
                'id' => $waitingPlayer->player->id,
                'name' => $waitingPlayer->player->full_name ?: $waitingPlayer->player->first_name,
            ])->values()->all(),
        ];
    }

    private function shufflePlayers(Collection $players): Collection
    {
        return collect($players->shuffle()->values());
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

    private function bestDoublePairing(Collection $players, array $partnerHistory): array
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
            $score = 0;
            foreach ($option as $pair) {
                $key = $this->pairKey((int) $pair[0]->id, (int) $pair[1]->id);
                $pairStats = $partnerHistory[$key] ?? [
                    'count' => 0,
                    'last_round' => 0,
                ];

                $score += ($pairStats['count'] * 1000) + $pairStats['last_round'];
            }

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
        ];
    }

    private function pairKey(int $a, int $b): string
    {
        $ordered = [$a, $b];
        sort($ordered, SORT_NUMERIC);

        return $ordered[0] . ':' . $ordered[1];
    }
}
