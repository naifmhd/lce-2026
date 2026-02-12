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
        Schema::create('pledge', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voter_id')
                ->unique()
                ->constrained('voter_records')
                ->cascadeOnDelete();
            $table->string('mayor')->nullable();
            $table->string('raeesa')->nullable();
            $table->string('council')->nullable();
            $table->string('wdc')->nullable();
            $table->timestamps();
        });

        Schema::table('voter_records', function (Blueprint $table) {
            $table->dropColumn(['mayor', 'raeesa', 'council', 'wdc']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('voter_records', function (Blueprint $table) {
            $table->string('mayor')->nullable();
            $table->string('raeesa')->nullable();
            $table->string('council')->nullable();
            $table->string('wdc')->nullable();
        });

        Schema::dropIfExists('pledge');
    }
};
