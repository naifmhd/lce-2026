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
        Schema::create('box_race_results', function (Blueprint $table) {
            $table->id();
            $table->string('registered_box');
            $table->foreignId('race_id')->constrained('election_races')->cascadeOnDelete();
            $table->unsignedInteger('invalid_votes')->default(0);
            $table->unique(['registered_box', 'race_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('box_race_results');
    }
};
