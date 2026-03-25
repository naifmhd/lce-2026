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
        Schema::table('election_races', function (Blueprint $table) {
            $table->string('projected_winner')->nullable()->after('sort_order');
            $table->string('projection_confidence')->nullable()->after('projected_winner');
            $table->text('projection_reasoning')->nullable()->after('projection_confidence');
            $table->timestamp('projection_updated_at')->nullable()->after('projection_reasoning');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('election_races', function (Blueprint $table) {
            $table->dropColumn(['projected_winner', 'projection_confidence', 'projection_reasoning', 'projection_updated_at']);
        });
    }
};
