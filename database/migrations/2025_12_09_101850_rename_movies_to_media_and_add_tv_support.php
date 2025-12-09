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
        // Rename the table from movies to media
        Schema::rename('movies', 'media');

        // Add new columns for TV show support
        Schema::table('media', function (Blueprint $table) {
            // Type field to distinguish between movie and tv
            $table->enum('type', ['movie', 'tv'])->default('movie')->after('tmdb_id');
            
            // Rename title to name (TMDB uses 'name' for TV shows)
            $table->renameColumn('title', 'name');
            
            // Add original_name field
            $table->string('original_name')->nullable()->after('name');
            
            // Add first_air_date for TV shows (keep release_date for movies)
            $table->date('first_air_date')->nullable()->after('release_date');
            
            // TV-specific fields
            $table->integer('number_of_seasons')->nullable()->after('first_air_date');
            $table->integer('number_of_episodes')->nullable()->after('number_of_seasons');
            
            // Add overview/description
            $table->text('overview')->nullable()->after('poster_path');
        });

        // Update the watchlists table to reference media instead of movies
        Schema::table('watchlists', function (Blueprint $table) {
            $table->renameColumn('movie_id', 'media_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert watchlists table
        Schema::table('watchlists', function (Blueprint $table) {
            $table->renameColumn('media_id', 'movie_id');
        });

        // Remove added columns
        Schema::table('media', function (Blueprint $table) {
            $table->dropColumn([
                'type',
                'original_name',
                'first_air_date',
                'number_of_seasons',
                'number_of_episodes',
                'overview'
            ]);
            
            // Rename back to title
            $table->renameColumn('name', 'title');
        });

        // Rename table back to movies
        Schema::rename('media', 'movies');
    }
};
