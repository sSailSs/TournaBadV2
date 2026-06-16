<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tournament_teams', function (Blueprint $table) {
            $table->foreignId('player_one_id')->nullable()->after('name')->constrained('players')->nullOnDelete();
            $table->foreignId('player_two_id')->nullable()->after('player_one_id')->constrained('players')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tournament_teams', function (Blueprint $table) {
            $table->dropConstrainedForeignId('player_two_id');
            $table->dropConstrainedForeignId('player_one_id');
        });
    }
};
