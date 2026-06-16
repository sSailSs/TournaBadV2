<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_match_id')->constrained('tournament_matches')->cascadeOnDelete();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->unsignedTinyInteger('team_number');
            $table->timestamps();

            $table->unique(['tournament_match_id', 'player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_players');
    }
};
