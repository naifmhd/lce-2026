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
            $table->text('cc_remarks')->nullable()->after('vote_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('voter_records', function (Blueprint $table) {
            $table->dropColumn('cc_remarks');
        });
    }
};
