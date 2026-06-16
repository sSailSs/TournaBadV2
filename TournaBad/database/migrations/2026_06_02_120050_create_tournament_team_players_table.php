<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tournament_team_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_team_id')->constrained('tournament_teams')->cascadeOnDelete();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['tournament_team_id', 'player_id']);
            $table->index(['tournament_team_id', 'position']);
        });

        if (Schema::hasColumn('tournament_teams', 'player_one_id')) {
            $now = now();
            $teams = DB::table('tournament_teams')->get();

            foreach ($teams as $team) {
                foreach ([
                    1 => $team->player_one_id ?? null,
                    2 => $team->player_two_id ?? null,
                    3 => $team->player_three_id ?? null,
                ] as $position => $playerId) {
                    if (! $playerId) {
                        continue;
                    }

                    DB::table('tournament_team_players')->insertOrIgnore([
                        'tournament_team_id' => $team->id,
                        'player_id' => $playerId,
                        'position' => $position,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournament_team_players');
    }
};
