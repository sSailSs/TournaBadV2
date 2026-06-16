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
        Schema::table('tournaments', function (Blueprint $table) {
            $table->string('team_display_mode')->default('players')->after('team_size');
        });

        Schema::table('tournament_teams', function (Blueprint $table) {
            $table->string('team_label')->nullable()->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tournament_teams', function (Blueprint $table) {
            $table->dropColumn('team_label');
        });

        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropColumn('team_display_mode');
        });
    }
};
