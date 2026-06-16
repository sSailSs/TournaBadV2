<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualPlayerPointsUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_update_player_points_manually(): void
    {
        $user = User::factory()->create();

        $tournament = Tournament::create([
            'creator_id' => $user->id,
            'name' => 'Tournoi points manuels',
            'starts_on' => now()->addDay()->toDateString(),
            'courts_count' => 1,
            'round_duration_minutes' => 10,
            'status' => 'draft',
            'description' => null,
        ]);

        $player = Player::create([
            'tournament_id' => $tournament->id,
            'first_name' => 'Joueur test',
            'is_active' => true,
            'points' => 11,
            'manual_points_adjustment' => 0,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->patch(route('tournaments.players.points.update', [$tournament, $player]), [
            'points' => 20,
        ]);

        $response->assertRedirect(route('tournaments.points', $tournament));

        $this->assertSame(9, $player->fresh()->manual_points_adjustment);
    }
}
