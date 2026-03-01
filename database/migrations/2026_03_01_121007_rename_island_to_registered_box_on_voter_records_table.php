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
            $table->renameColumn('island', 'registered_box');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('voter_records', function (Blueprint $table) {
            $table->renameColumn('registered_box', 'island');
        });
    }
};
