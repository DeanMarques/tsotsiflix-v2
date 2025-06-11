<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->unsignedBigInteger('tmdb_id')->unique();
            $table->text('overview');
            $table->string('poster_path')->nullable();
            $table->string('backdrop_path')->nullable();
            $table->date('release_date');
            $table->float('vote_average');
            $table->string('local_path');
            $table->string('trailer_url')->nullable();
            $table->string('director')->nullable();
            $table->json('cast')->nullable();
            $table->integer('runtime')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movies');
    }
};
