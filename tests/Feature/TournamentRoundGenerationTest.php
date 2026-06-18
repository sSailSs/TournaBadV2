<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\Round;
use App\Models\Tournament;
use App\Models\TournamentTeam;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TournamentRoundGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_double_round_requires_at_least_four_players(): void
    {
        $user = User::factory()->create();

        $tournament = Tournament::create([
            'creator_id' => $user->id,
            'name' => 'Tournoi sans joueurs',
            'starts_on' => now()->addDay()->toDateString(),
            'courts_count' => 2,
            'round_duration_minutes' => 12,
            'format' => 'double',
            'allow_2v1' => false,
            'allow_1v1' => false,
            'status' => 'draft',
            'description' => null,
        ]);

        $response = $this->actingAs($user)->postJson(route('tournaments.rounds.generate', $tournament));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['players'])
            ->assertJsonPath('errors.players.0', 'Renseigne au moins 4 joueurs pour générer un tour.');

        $this->assertDatabaseMissing('rounds', [
            'tournament_id' => $tournament->id,
        ]);
    }

    public function test_random_team_round_requires_at_least_four_players(): void
    {
        $user = User::factory()->create();

        $tournament = Tournament::create([
            'creator_id' => $user->id,
            'name' => 'Tournoi equipe aleatoire',
            'starts_on' => now()->addDay()->toDateString(),
            'courts_count' => 2,
            'round_duration_minutes' => 12,
            'format' => 'team',
            'team_assignment_mode' => 'random',
            'team_size' => 2,
            'status' => 'draft',
            'description' => null,
        ]);

        $response = $this->actingAs($user)->postJson(route('tournaments.rounds.generate', $tournament));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['players'])
            ->assertJsonPath('errors.players.0', 'Renseigne au moins 4 joueurs pour générer un tour.');

        $this->assertDatabaseMissing('rounds', [
            'tournament_id' => $tournament->id,
        ]);
    }

    public function test_predefined_team_round_requires_two_complete_teams(): void
    {
        $user = User::factory()->create();

        $tournament = Tournament::create([
            'creator_id' => $user->id,
            'name' => 'Tournoi equipes predefinies',
            'starts_on' => now()->addDay()->toDateString(),
            'courts_count' => 2,
            'round_duration_minutes' => 12,
            'format' => 'team',
            'team_assignment_mode' => 'predefined',
            'team_size' => 2,
            'status' => 'draft',
            'description' => null,
        ]);

        $team = TournamentTeam::create([
            'tournament_id' => $tournament->id,
            'name' => 'Equipe 1',
            'position' => 1,
        ]);

        foreach (['Alice', 'Bob'] as $index => $name) {
            $player = Player::create([
                'tournament_id' => $tournament->id,
                'first_name' => $name,
                'last_name' => null,
                'email' => null,
                'is_active' => true,
                'created_by' => $user->id,
            ]);

            $team->players()->attach($player->id, [
                'position' => $index + 1,
            ]);
        }

        $response = $this->actingAs($user)->postJson(route('tournaments.rounds.generate', $tournament));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['teams'])
            ->assertJsonPath('errors.teams.0', 'Renseigne au moins 2 équipes complètes avec 2 joueurs minimum par équipe.');

        $this->assertDatabaseMissing('rounds', [
            'tournament_id' => $tournament->id,
        ]);
    }

    public function test_authenticated_user_can_generate_a_round(): void
    {
        $user = User::factory()->create();

        $tournament = Tournament::create([
            'creator_id' => $user->id,
            'name' => 'Tournoi test',
            'starts_on' => now()->addDay()->toDateString(),
            'courts_count' => 2,
            'round_duration_minutes' => 12,
            'status' => 'draft',
            'description' => null,
        ]);

        foreach (range(1, 7) as $index) {
            Player::create([
                'tournament_id' => $tournament->id,
                'first_name' => 'Joueur '.$index,
                'last_name' => null,
                'email' => null,
                'is_active' => true,
                'created_by' => $user->id,
            ]);
        }

        $response = $this->actingAs($user)->postJson(route('tournaments.rounds.generate', $tournament), [
            'allow2v1' => 1,
        ]);

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

    public function test_double_rounds_do_not_repeat_partners_until_needed(): void
    {
        $user = User::factory()->create();

        $tournament = Tournament::create([
            'creator_id' => $user->id,
            'name' => 'Tournoi double partenaires',
            'starts_on' => now()->addDay()->toDateString(),
            'courts_count' => 2,
            'round_duration_minutes' => 12,
            'status' => 'draft',
            'description' => null,
        ]);

        foreach (range(1, 8) as $index) {
            Player::create([
                'tournament_id' => $tournament->id,
                'first_name' => 'Joueur '.$index,
                'last_name' => null,
                'email' => null,
                'is_active' => true,
                'created_by' => $user->id,
            ]);
        }

        $partnerPairs = [];

        for ($round = 1; $round <= 3; $round++) {
            $response = $this->actingAs($user)->postJson(route('tournaments.rounds.generate', $tournament));
            $response->assertOk();

            foreach ($response->json('round.matches') as $match) {
                foreach (['team_a', 'team_b'] as $teamKey) {
                    $partnerPairs[] = collect($match[$teamKey])
                        ->sort()
                        ->values()
                        ->implode(' / ');
                }
            }

            $tournament->rounds()->latest('round_number')->firstOrFail()->update([
                'generated_at' => now()->subSeconds(5),
            ]);
        }

        $this->assertCount(12, $partnerPairs);
        $this->assertSame($partnerPairs, array_values(array_unique($partnerPairs)));
    }

    public function test_random_double_tournament_with_26_players_and_6_courts_rotates_partners(): void
    {
        $user = User::factory()->create();

        $tournament = Tournament::create([
            'creator_id' => $user->id,
            'name' => 'Tournoi double 26 joueurs',
            'starts_on' => now()->addDay()->toDateString(),
            'courts_count' => 6,
            'round_duration_minutes' => 12,
            'status' => 'draft',
            'description' => null,
        ]);

        foreach (range(1, 26) as $index) {
            Player::create([
                'tournament_id' => $tournament->id,
                'first_name' => 'Joueur '.$index,
                'last_name' => null,
                'email' => null,
                'is_active' => true,
                'created_by' => $user->id,
            ]);
        }

        $partnerPairs = [];

        for ($round = 1; $round <= 8; $round++) {
            $response = $this->actingAs($user)->postJson(route('tournaments.rounds.generate', $tournament));
            $response->assertOk();

            $payload = $response->json('round');

            $this->assertCount(6, $payload['matches']);
            $this->assertCount(2, $payload['waiting']);
            $this->assertSame(['double'], collect($payload['matches'])->pluck('match_type')->unique()->values()->all());

            foreach ($payload['matches'] as $match) {
                foreach (['team_a', 'team_b'] as $teamKey) {
                    $partnerPairs[] = collect($match[$teamKey])
                        ->sort()
                        ->values()
                        ->implode(' / ');
                }
            }

            $tournament->rounds()->latest('round_number')->firstOrFail()->update([
                'generated_at' => now()->subSeconds(5),
            ]);
        }

        $this->assertCount(96, $partnerPairs);
        $this->assertSame($partnerPairs, array_values(array_unique($partnerPairs)));
    }
}
