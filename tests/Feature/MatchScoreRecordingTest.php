<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchScoreRecordingTest extends TestCase
{
    use RefreshDatabase;

    public function test_score_recording_updates_individual_player_points(): void
    {
        $user = User::factory()->create();

        $tournament = Tournament::create([
            'creator_id' => $user->id,
            'name' => 'Tournoi score',
            'starts_on' => now()->addDay()->toDateString(),
            'courts_count' => 1,
            'round_duration_minutes' => 12,
            'status' => 'draft',
            'description' => null,
        ]);

        foreach (range(1, 4) as $index) {
            Player::create([
                'tournament_id' => $tournament->id,
                'first_name' => 'Joueur '.$index,
                'last_name' => null,
                'email' => null,
                'is_active' => true,
                'points' => 0,
                'created_by' => $user->id,
            ]);
        }

        $this->actingAs($user)->postJson(route('tournaments.rounds.generate', $tournament), [
            'allow2v1' => 0,
        ])->assertOk();

        $match = TournamentMatch::query()->where('tournament_id', $tournament->id)->firstOrFail();
        $match->load('players');

        $teamOnePlayers = $match->players->filter(fn (Player $player) => (int) $player->pivot->team_number === 1);
        $teamTwoPlayers = $match->players->filter(fn (Player $player) => (int) $player->pivot->team_number === 2);

        $this->actingAs($user)->postJson(route('tournaments.matches.score', [$tournament, $match]), [
            'team_one_score' => 11,
            'team_two_score' => 7,
        ])->assertOk();

        foreach ($teamOnePlayers as $player) {
            $this->assertSame(11, $player->fresh()->points);
        }

        foreach ($teamTwoPlayers as $player) {
            $this->assertSame(7, $player->fresh()->points);
        }
    }

    public function test_owner_can_delete_current_round_and_rollback_scores(): void
    {
        $user = User::factory()->create();

        $tournament = Tournament::create([
            'creator_id' => $user->id,
            'name' => 'Tournoi suppression tour',
            'starts_on' => now()->addDay()->toDateString(),
            'courts_count' => 1,
            'round_duration_minutes' => 12,
            'status' => 'draft',
            'description' => null,
        ]);

        foreach (range(1, 5) as $index) {
            Player::create([
                'tournament_id' => $tournament->id,
                'first_name' => 'Joueur '.$index,
                'last_name' => null,
                'email' => null,
                'is_active' => true,
                'points' => 0,
                'created_by' => $user->id,
            ]);
        }

        $this->actingAs($user)->postJson(route('tournaments.rounds.generate', $tournament))->assertOk();

        $round = $tournament->rounds()->firstOrFail();
        $match = TournamentMatch::query()->where('tournament_id', $tournament->id)->firstOrFail();

        $this->actingAs($user)->postJson(route('tournaments.matches.score', [$tournament, $match]), [
            'team_one_score' => 11,
            'team_two_score' => 7,
        ])->assertOk();

        $this->assertSame(36, $tournament->players()->sum('points'));
        $this->assertSame(1, $round->waitingPlayers()->count());

        $response = $this->actingAs($user)->delete(route('tournaments.rounds.destroy', [$tournament, $round]));

        $response->assertRedirect(route('tournaments.show', $tournament));
        $this->assertDatabaseMissing('rounds', ['id' => $round->id]);
        $this->assertDatabaseMissing('tournament_matches', ['id' => $match->id]);
        $this->assertSame(0, $tournament->players()->sum('points'));
    }
}
