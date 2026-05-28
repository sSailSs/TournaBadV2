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
        if (! Schema::hasColumn('tournaments', 'format')) {
            Schema::table('tournaments', function (Blueprint $table) {
                $table->string('format')->default('double')->after('creator_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('tournaments', 'format')) {
            Schema::table('tournaments', function (Blueprint $table) {
                $table->dropColumn('format');
            });
        }
    }
};