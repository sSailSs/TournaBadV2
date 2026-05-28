<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table): void {
            if (! Schema::hasColumn('tournaments', 'round_duration_seconds')) {
                $table->unsignedInteger('round_duration_seconds')->nullable()->after('round_duration_minutes');
            }
        });

        if (Schema::hasColumn('tournaments', 'round_duration_seconds') && Schema::hasColumn('tournaments', 'round_duration_minutes')) {
            DB::table('tournaments')
                ->whereNull('round_duration_seconds')
                ->update([
                    'round_duration_seconds' => DB::raw('round_duration_minutes * 60'),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table): void {
            if (Schema::hasColumn('tournaments', 'round_duration_seconds')) {
                $table->dropColumn('round_duration_seconds');
            }
        });
    }
};