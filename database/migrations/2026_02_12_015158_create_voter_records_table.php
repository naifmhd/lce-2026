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
        Schema::create('voter_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('list_number')->unique();
            $table->string('id_card_number')->nullable()->index();
            $table->string('photo_path')->nullable();
            $table->string('name')->nullable();
            $table->string('sex', 20)->nullable();
            $table->string('mobile', 30)->nullable();
            $table->date('dob')->nullable();
            $table->unsignedTinyInteger('age')->nullable();
            $table->string('island')->nullable();
            $table->string('majilis_con')->nullable();
            $table->string('address')->nullable();
            $table->string('dhaairaa')->nullable();
            $table->string('mayor')->nullable();
            $table->string('raeesa')->nullable();
            $table->string('council')->nullable();
            $table->string('wdc')->nullable();
            $table->string('re_reg_travel')->nullable();
            $table->text('comments')->nullable();
            $table->string('vote_status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voter_records');
    }
};
