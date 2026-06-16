<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('round_id')->constrained('rounds')->cascadeOnDelete();
            $table->foreignId('tournament_id')->constrained('tournaments')->cascadeOnDelete();
            $table->unsignedTinyInteger('court_number');
            $table->string('match_type')->default('double');
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->unique(['round_id', 'court_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_matches');
    }
};
