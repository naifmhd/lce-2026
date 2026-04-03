<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('box_results', function (Blueprint $table) {
            $table->dropUnique(['registered_box']);
            $table->string('election_type')->default('local_council')->after('registered_box');
            $table->unique(['registered_box', 'election_type']);
        });
    }

    public function down(): void
    {
        Schema::table('box_results', function (Blueprint $table) {
            $table->dropUnique(['registered_box', 'election_type']);
            $table->dropColumn('election_type');
            $table->unique(['registered_box']);
        });
    }
};
