<?php

namespace Tests\Feature;

use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamTournamentCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_random_team_tournament(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('tournaments.store'), [
            'creation_type' => 'team',
            'name' => 'Tournoi equipes aleatoires',
            'starts_on' => now()->addDay()->toDateString(),
            'courts_count' => 3,
            'round_duration_minutes' => 12,
            'round_duration_seconds' => 0,
            'description' => null,
            'team_assignment_mode' => 'random',
            'team_size' => 4,
        ]);

        $tournament = Tournament::query()->firstOrFail();

        $response->assertRedirect(route('tournaments.players', $tournament));

        $this->assertSame('team', $tournament->format);
        $this->assertSame('random', $tournament->team_assignment_mode);
        $this->assertSame(4, $tournament->team_size);
        $this->assertCount(0, $tournament->teams);
    }

    public function test_user_cannot_create_more_than_five_tournaments(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $index) {
            Tournament::create([
                'creator_id' => $user->id,
                'name' => 'Tournoi '.$index,
                'starts_on' => now()->addDay()->toDateString(),
                'courts_count' => 2,
                'round_duration_minutes' => 12,
                'round_duration_seconds' => 720,
                'status' => 'draft',
                'description' => null,
            ]);
        }

        $response = $this->actingAs($user)->post(route('tournaments.store'), [
            'creation_type' => 'double',
            'name' => 'Tournoi en trop',
            'starts_on' => now()->addDay()->toDateString(),
            'courts_count' => 2,
            'round_duration_minutes' => 12,
            'round_duration_seconds' => 0,
            'description' => null,
        ]);

        $response->assertSessionHasErrors([
            'name' => 'Tu as atteint la limite de 5 tournois par compte.',
        ]);

        $this->assertSame(5, $user->tournaments()->count());
    }

    public function test_user_can_create_predefined_team_tournament(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('tournaments.store'), [
            'creation_type' => 'team',
            'name' => 'Tournoi equipes predefinies',
            'starts_on' => now()->addDay()->toDateString(),
            'courts_count' => 2,
            'round_duration_minutes' => 10,
            'round_duration_seconds' => 30,
            'description' => null,
            'team_assignment_mode' => 'predefined',
        ]);

        $tournament = Tournament::query()->with(['players', 'teams.players'])->firstOrFail();

        $response->assertRedirect(route('tournaments.teams', $tournament));

        $this->assertSame('team', $tournament->format);
        $this->assertSame('predefined', $tournament->team_assignment_mode);
        $this->assertNull($tournament->team_size);
        $this->assertCount(0, $tournament->players);
        $this->assertCount(0, $tournament->teams);
    }

    public function test_user_can_add_predefined_team_after_tournament_creation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('tournaments.store'), [
            'creation_type' => 'team',
            'name' => 'Tournoi equipes predefinies',
            'starts_on' => now()->addDay()->toDateString(),
            'courts_count' => 2,
            'round_duration_minutes' => 10,
            'round_duration_seconds' => 30,
            'description' => null,
            'team_assignment_mode' => 'predefined',
        ]);

        $tournament = Tournament::query()->firstOrFail();

        $response = $this->actingAs($user)->post(route('tournaments.teams.store', $tournament), [
            'team_label' => 'Les Smashers',
            'player_names' => ['Alice', 'Bruno'],
        ]);

        $tournament->load(['players', 'teams.players']);

        $response->assertRedirect(route('tournaments.teams', $tournament));

        $this->assertSame(['Alice', 'Bruno'], $tournament->players->pluck('first_name')->all());
        $this->assertSame(['Alice / Bruno'], $tournament->teams->pluck('name')->all());
        $this->assertSame(['Les Smashers'], $tournament->teams->pluck('team_label')->all());
        $this->assertSame(['Alice', 'Bruno'], $tournament->teams[0]->players->pluck('first_name')->all());
    }

    public function test_predefined_team_round_keeps_players_together(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('tournaments.store'), [
            'creation_type' => 'team',
            'name' => 'Tournoi equipes fixes',
            'starts_on' => now()->addDay()->toDateString(),
            'courts_count' => 1,
            'round_duration_minutes' => 10,
            'round_duration_seconds' => 0,
            'description' => null,
            'team_assignment_mode' => 'predefined',
        ]);

        $tournament = Tournament::query()->firstOrFail();

        foreach ([['Alice', 'Bruno'], ['Chloe', 'David'], ['Emma', 'Farid']] as $team) {
            $this->actingAs($user)->post(route('tournaments.teams.store', $tournament), [
                'player_names' => $team,
            ]);
        }

        $tournament->load('teams');

        $response = $this->actingAs($user)->postJson(route('tournaments.rounds.generate', $tournament));

        $response->assertOk();

        $payload = $response->json('round');

        $this->assertCount(1, $payload['matches']);
        $this->assertCount(1, $payload['matches'][0]['team_a']);
        $this->assertCount(1, $payload['matches'][0]['team_b']);
        $this->assertCount(1, $payload['waiting']);

        $playedPairs = collect([$payload['matches'][0]['team_a_display'], $payload['matches'][0]['team_b_display']])
            ->map(fn (array $team) => collect(explode(' / ', $team['label']))->sort()->values()->implode(' / '))
            ->all();

        $knownPairs = $tournament->teams->pluck('name')->all();

        foreach ($playedPairs as $playedPair) {
            $this->assertContains($playedPair, $knownPairs);
        }
    }

    public function test_predefined_team_round_avoids_repeating_same_opposition_when_possible(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('tournaments.store'), [
            'creation_type' => 'team',
            'name' => 'Tournoi equipes fixes',
            'starts_on' => now()->addDay()->toDateString(),
            'courts_count' => 1,
            'round_duration_minutes' => 10,
            'round_duration_seconds' => 0,
            'description' => null,
            'team_assignment_mode' => 'predefined',
        ]);

        $tournament = Tournament::query()->firstOrFail();

        foreach ([['Alice', 'Bruno'], ['Chloe', 'David'], ['Emma', 'Farid']] as $team) {
            $this->actingAs($user)->post(route('tournaments.teams.store', $tournament), [
                'player_names' => $team,
            ]);
        }

        $firstResponse = $this->actingAs($user)->postJson(route('tournaments.rounds.generate', $tournament));
        $firstResponse->assertOk();

        $tournament->rounds()->latest('round_number')->firstOrFail()->update([
            'generated_at' => now()->subSeconds(5),
        ]);

        $secondResponse = $this->actingAs($user)->postJson(route('tournaments.rounds.generate', $tournament));
        $secondResponse->assertOk();

        $firstOpposition = collect([
            $firstResponse->json('round.matches.0.team_a_display.label'),
            $firstResponse->json('round.matches.0.team_b_display.label'),
        ])
            ->sort()
            ->values()
            ->implode(' vs ');

        $secondOpposition = collect([
            $secondResponse->json('round.matches.0.team_a_display.label'),
            $secondResponse->json('round.matches.0.team_b_display.label'),
        ])
            ->sort()
            ->values()
            ->implode(' vs ');

        $this->assertNotSame($firstOpposition, $secondOpposition);
    }

    public function test_predefined_team_round_robin_plan_avoids_early_repeats_across_full_courts(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('tournaments.store'), [
            'creation_type' => 'team',
            'name' => 'Tournoi equipes round robin',
            'starts_on' => now()->addDay()->toDateString(),
            'courts_count' => 3,
            'round_duration_minutes' => 10,
            'round_duration_seconds' => 0,
            'description' => null,
            'team_assignment_mode' => 'predefined',
        ]);

        $tournament = Tournament::query()->firstOrFail();

        foreach (range(1, 6) as $index) {
            $this->actingAs($user)->post(route('tournaments.teams.store', $tournament), [
                'team_label' => 'Equipe '.$index,
                'player_names' => ['Joueur '.$index.'A', 'Joueur '.$index.'B'],
            ]);
        }

        $oppositions = [];

        for ($round = 1; $round <= 3; $round++) {
            $response = $this->actingAs($user)->postJson(route('tournaments.rounds.generate', $tournament));
            $response->assertOk();

            foreach ($response->json('round.matches') as $match) {
                $oppositions[] = collect([
                    $match['team_a_display']['label'],
                    $match['team_b_display']['label'],
                ])
                    ->sort()
                    ->values()
                    ->implode(' vs ');
            }

            $tournament->rounds()->latest('round_number')->firstOrFail()->update([
                'generated_at' => now()->subSeconds(5),
            ]);
        }

        $this->assertCount(9, $oppositions);
        $this->assertSame($oppositions, array_values(array_unique($oppositions)));
    }

    public function test_predefined_team_tournament_with_24_players_and_6_courts_completes_11_unique_rotations(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('tournaments.store'), [
            'creation_type' => 'team',
            'name' => 'Tournoi equipes 24 joueurs',
            'starts_on' => now()->addDay()->toDateString(),
            'courts_count' => 6,
            'round_duration_minutes' => 10,
            'round_duration_seconds' => 0,
            'description' => null,
            'team_assignment_mode' => 'predefined',
        ]);

        $tournament = Tournament::query()->firstOrFail();

        foreach (range(1, 12) as $index) {
            $this->actingAs($user)->post(route('tournaments.teams.store', $tournament), [
                'team_label' => 'Equipe '.$index,
                'player_names' => ['Joueur '.$index.'A', 'Joueur '.$index.'B'],
            ]);
        }

        $oppositions = [];

        for ($round = 1; $round <= 11; $round++) {
            $response = $this->actingAs($user)->postJson(route('tournaments.rounds.generate', $tournament));
            $response->assertOk();

            $payload = $response->json('round');

            $this->assertCount(6, $payload['matches']);
            $this->assertCount(0, $payload['waiting']);
            $this->assertSame(['team_double'], collect($payload['matches'])->pluck('match_type')->unique()->values()->all());

            foreach ($payload['matches'] as $match) {
                $oppositions[] = collect([
                    $match['team_a_display']['label'],
                    $match['team_b_display']['label'],
                ])
                    ->sort()
                    ->values()
                    ->implode(' vs ');
            }

            $tournament->rounds()->latest('round_number')->firstOrFail()->update([
                'generated_at' => now()->subSeconds(5),
            ]);
        }

        $this->assertCount(66, $oppositions);
        $this->assertSame($oppositions, array_values(array_unique($oppositions)));
    }

    public function test_user_can_make_predefined_team_incomplete_and_complete_it_again(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('tournaments.store'), [
            'creation_type' => 'team',
            'name' => 'Tournoi equipes predefinies',
            'starts_on' => now()->addDay()->toDateString(),
            'courts_count' => 2,
            'round_duration_minutes' => 10,
            'round_duration_seconds' => 30,
            'description' => null,
            'team_assignment_mode' => 'predefined',
        ]);

        $tournament = Tournament::query()->firstOrFail();

        $this->actingAs($user)->post(route('tournaments.teams.store', $tournament), [
            'player_names' => ['Alice', 'Bruno'],
        ]);

        $team = $tournament->teams()->firstOrFail();

        $this->actingAs($user)->patch(route('tournaments.teams.update', [$tournament, $team]), [
            'team_label' => '',
            'player_names' => ['Alice'],
        ])->assertRedirect(route('tournaments.teams', $tournament));

        $team->refresh();
        $team->load('players');
        $this->assertSame(['Alice'], $team->players->pluck('first_name')->all());
        $this->assertSame('Alice', $team->name);

        $this->actingAs($user)->patch(route('tournaments.teams.update', [$tournament, $team]), [
            'team_label' => 'Les Volants',
            'player_names' => ['Alice', 'Camille', 'Bruno'],
        ])->assertRedirect(route('tournaments.teams', $tournament));

        $team->refresh();
        $team->load('players');
        $this->assertSame('Alice / Camille / Bruno', $team->name);
        $this->assertSame('Les Volants', $team->team_label);
        $this->assertSame(['Alice', 'Camille', 'Bruno'], $team->players->pluck('first_name')->all());
    }

    public function test_team_display_mode_uses_team_labels_with_player_names_as_fallback(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('tournaments.store'), [
            'creation_type' => 'team',
            'name' => 'Tournoi equipes nommees',
            'starts_on' => now()->addDay()->toDateString(),
            'courts_count' => 1,
            'round_duration_minutes' => 10,
            'round_duration_seconds' => 0,
            'description' => null,
            'team_assignment_mode' => 'predefined',
        ]);

        $tournament = Tournament::query()->firstOrFail();

        $this->actingAs($user)->post(route('tournaments.teams.store', $tournament), [
            'team_label' => 'Les Smashers',
            'player_names' => ['Alice', 'Bruno'],
        ]);
        $this->actingAs($user)->post(route('tournaments.teams.store', $tournament), [
            'team_label' => '',
            'player_names' => ['Chloe', 'David'],
        ]);
        $response = $this->actingAs($user)->postJson(route('tournaments.rounds.generate', $tournament));
        $response->assertOk();

        $displayedTeams = collect([
            $response->json('round.matches.0.team_a_display.label'),
            $response->json('round.matches.0.team_b_display.label'),
        ])->sort()->values()->all();

        $this->assertSame(['Chloe / David', 'Les Smashers'], $displayedTeams);
        $this->assertSame('Alice / Bruno', $response->json('round.matches.0.team_a_display.label') === 'Les Smashers'
            ? $response->json('round.matches.0.team_a_display.title')
            : $response->json('round.matches.0.team_b_display.title'));
    }

    public function test_three_player_team_randomly_selects_two_players_for_round(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('tournaments.store'), [
            'creation_type' => 'team',
            'name' => 'Tournoi equipes a trois',
            'starts_on' => now()->addDay()->toDateString(),
            'courts_count' => 1,
            'round_duration_minutes' => 10,
            'round_duration_seconds' => 0,
            'description' => null,
            'team_assignment_mode' => 'predefined',
        ]);

        $tournament = Tournament::query()->firstOrFail();

        $this->actingAs($user)->post(route('tournaments.teams.store', $tournament), [
            'team_label' => 'Equipe A',
            'player_names' => ['Alice', 'Bruno', 'Camille'],
        ]);
        $this->actingAs($user)->post(route('tournaments.teams.store', $tournament), [
            'team_label' => 'Equipe B',
            'player_names' => ['David', 'Emma'],
        ]);

        $response = $this->actingAs($user)->postJson(route('tournaments.rounds.generate', $tournament));
        $response->assertOk();

        $match = TournamentMatch::query()
            ->with('players')
            ->where('tournament_id', $tournament->id)
            ->firstOrFail();

        $teamAPlayers = ['Alice', 'Bruno', 'Camille'];
        $selectedTeamAPlayers = $match->players
            ->pluck('first_name')
            ->filter(fn (string $name) => in_array($name, $teamAPlayers, true))
            ->values()
            ->all();

        $this->assertCount(2, $selectedTeamAPlayers);
    }

    public function test_large_team_randomly_selects_two_players_for_round(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('tournaments.store'), [
            'creation_type' => 'team',
            'name' => 'Tournoi grandes equipes',
            'starts_on' => now()->addDay()->toDateString(),
            'courts_count' => 1,
            'round_duration_minutes' => 10,
            'round_duration_seconds' => 0,
            'description' => null,
            'team_assignment_mode' => 'predefined',
        ]);

        $tournament = Tournament::query()->firstOrFail();

        $largeTeamPlayers = ['Alice', 'Bruno', 'Camille', 'Diane', 'Etienne'];

        $this->actingAs($user)->post(route('tournaments.teams.store', $tournament), [
            'team_label' => 'Grande equipe',
            'player_names' => $largeTeamPlayers,
        ]);
        $this->actingAs($user)->post(route('tournaments.teams.store', $tournament), [
            'team_label' => 'Equipe B',
            'player_names' => ['Farid', 'Gaelle'],
        ]);

        $this->assertSame(5, $tournament->teams()->firstOrFail()->players()->count());

        $response = $this->actingAs($user)->postJson(route('tournaments.rounds.generate', $tournament));
        $response->assertOk();

        $match = TournamentMatch::query()
            ->with('players')
            ->where('tournament_id', $tournament->id)
            ->firstOrFail();

        $selectedLargeTeamPlayers = $match->players
            ->pluck('first_name')
            ->filter(fn (string $name) => in_array($name, $largeTeamPlayers, true))
            ->values()
            ->all();

        $this->assertCount(2, $selectedLargeTeamPlayers);
    }

    public function test_predefined_team_points_are_displayed_once_per_team(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('tournaments.store'), [
            'creation_type' => 'team',
            'name' => 'Tournoi points equipes',
            'starts_on' => now()->addDay()->toDateString(),
            'courts_count' => 1,
            'round_duration_minutes' => 10,
            'round_duration_seconds' => 0,
            'description' => null,
            'team_assignment_mode' => 'predefined',
        ]);

        $tournament = Tournament::query()->firstOrFail();

        $this->actingAs($user)->post(route('tournaments.teams.store', $tournament), [
            'team_label' => 'Grande equipe',
            'player_names' => ['Alice', 'Bruno', 'Camille'],
        ]);
        $this->actingAs($user)->post(route('tournaments.teams.store', $tournament), [
            'team_label' => 'Petite equipe',
            'player_names' => ['David', 'Emma'],
        ]);

        $this->actingAs($user)->postJson(route('tournaments.rounds.generate', $tournament))->assertOk();

        $match = TournamentMatch::query()
            ->where('tournament_id', $tournament->id)
            ->firstOrFail();

        $this->actingAs($user)->postJson(route('tournaments.matches.score', [$tournament, $match]), [
            'team_one_score' => 11,
            'team_two_score' => 7,
        ])->assertOk();

        $response = $this->actingAs($user)->get(route('tournaments.points', $tournament));

        $response->assertOk();
        $response->assertSee('Points des equipes');
        $response->assertSee('Grande equipe');
        $response->assertSee('Petite equipe');
        $response->assertSee('>11<', false);
        $response->assertSee('>7<', false);
        $response->assertDontSee('>22<', false);
        $response->assertDontSee('>33<', false);
    }
}
