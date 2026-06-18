<?php

namespace Database\Seeders;

use App\Models\Player;
use App\Models\Tournament;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'admin@tournabad.local'],
            [
                'name' => 'Admin TournaBad',
                'password' => 'password123',
                'is_admin' => true,
            ]
        );

        $tournament = Tournament::updateOrCreate(
            ['name' => 'Tournoi Interne Printemps'],
            [
                'creator_id' => $user->id,
                'format' => 'double',
                'starts_on' => now()->addWeek()->toDateString(),
                'courts_count' => 2,
                'round_duration_minutes' => 7,
                'round_duration_seconds' => 420,
                'status' => 'draft',
                'description' => 'Tournoi interne de lancement TournaBad',
            ]
        );

        $tournament->players()->forceDelete();

        $players = collect(range(1, 12))->map(fn (int $number) => [
            'first_name' => 'Joueur '.$number,
        ]);

        foreach ($players as $data) {
            Player::updateOrCreate(
                [
                    'tournament_id' => $tournament->id,
                    'first_name' => $data['first_name'],
                ],
                [
                    'tournament_id' => $tournament->id,
                    'first_name' => $data['first_name'],
                    'created_by' => $user->id,
                    'is_active' => true,
                ]
            );
        }
    }
}
