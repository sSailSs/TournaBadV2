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
        Schema::table('tournaments', function (Blueprint $table): void {
            if (! Schema::hasColumn('tournaments', 'alarm_audio_path')) {
                $table->string('alarm_audio_path')->nullable()->after('round_duration_minutes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table): void {
            if (Schema::hasColumn('tournaments', 'alarm_audio_path')) {
                $table->dropColumn('alarm_audio_path');
            }
        });
    }
};