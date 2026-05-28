<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\Round;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TournamentRoundGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_generate_a_round(): void
    {
        $user = User::factory()->create();

        $tournament = Tournament::create([
            'creator_id' => $user->id,
            'name' => 'Tournoi test',
            'starts_on' => now()->addDay()->toDateString(),
            'courts_count' => 2,
            'round_duration_minutes' => 12,
            'allow_2v1' => true,
            'status' => 'draft',
            'description' => null,
        ]);

        foreach (range(1, 7) as $index) {
            Player::create([
                'tournament_id' => $tournament->id,
                'first_name' => 'Joueur ' . $index,
                'last_name' => null,
                'email' => null,
                'is_active' => true,
                'created_by' => $user->id,
            ]);
        }

        $response = $this->actingAs($user)->postJson(route('tournaments.rounds.generate', $tournament));

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'round' => [
                    'id',
                    'round_number',
                    'status',
                    'generated_at',
                    'matches' => [
                        '*' => [
                            'id',
                            'court_number',
                            'match_type',
                            'status',
                            'team_a',
                            'team_b',
                        ],
                    ],
                    'waiting' => [
                        '*' => [
                            'id',
                            'name',
                        ],
                    ],
                ],
            ]);

        $round = Round::query()->where('tournament_id', $tournament->id)->firstOrFail();

        $this->assertSame(1, $round->round_number);
        $this->assertCount(2, $round->matches);
        $this->assertCount(0, $round->waitingPlayers);
    }
}
