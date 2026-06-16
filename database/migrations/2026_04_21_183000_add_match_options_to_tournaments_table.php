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
            if (! Schema::hasColumn('tournaments', 'allow_2v1')) {
                $table->boolean('allow_2v1')->default(false)->after('format');
            }

            if (! Schema::hasColumn('tournaments', 'allow_1v1')) {
                $table->boolean('allow_1v1')->default(false)->after('allow_2v1');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table): void {
            if (Schema::hasColumn('tournaments', 'allow_1v1')) {
                $table->dropColumn('allow_1v1');
            }

            if (Schema::hasColumn('tournaments', 'allow_2v1')) {
                $table->dropColumn('allow_2v1');
            }
        });
    }
};