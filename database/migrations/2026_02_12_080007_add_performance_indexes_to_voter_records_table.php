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
        Schema::table('voter_records', function (Blueprint $table) {
            $table->index('mobile');
            $table->index('dhaairaa');
            $table->index('majilis_con');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('voter_records', function (Blueprint $table) {
            $table->dropIndex(['mobile']);
            $table->dropIndex(['dhaairaa']);
            $table->dropIndex(['majilis_con']);
        });
    }
};
