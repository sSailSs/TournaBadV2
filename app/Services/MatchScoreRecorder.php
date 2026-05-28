<?php

namespace App\Services;

use App\Models\MatchScore;
use App\Models\TournamentMatch;
use Illuminate\Support\Facades\DB;

class MatchScoreRecorder
{
    public function record(TournamentMatch $match, int $teamOneScore, int $teamTwoScore, ?int $userId = null): MatchScore
    {
        return DB::transaction(function () use ($match, $teamOneScore, $teamTwoScore, $userId) {
            $match->loadMissing(['players', 'score']);
            $existingScore = $match->score;

            if ($existingScore) {
                $this->applyDelta($match, -$existingScore->team_one_score, -$existingScore->team_two_score);
                $existingScore->update([
                    'team_one_score' => $teamOneScore,
                    'team_two_score' => $teamTwoScore,
                    'recorded_by' => $userId,
                    'recorded_at' => now(),
                ]);

                $score = $existingScore;
            } else {
                $score = MatchScore::create([
                    'tournament_match_id' => $match->id,
                    'team_one_score' => $teamOneScore,
                    'team_two_score' => $teamTwoScore,
                    'recorded_by' => $userId,
                    'recorded_at' => now(),
                ]);
            }

            $this->applyDelta($match, $teamOneScore, $teamTwoScore);

            return $score;
        });
    }

    private function applyDelta(TournamentMatch $match, int $teamOneDelta, int $teamTwoDelta): void
    {
        foreach ($match->players as $player) {
            $teamNumber = (int) $player->pivot->team_number;
            $delta = $teamNumber === 1 ? $teamOneDelta : $teamTwoDelta;

            $player->forceFill([
                'points' => max(0, (int) $player->points + $delta),
            ])->save();
        }
    }
}
