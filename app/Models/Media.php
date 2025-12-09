<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    use HasFactory;

    protected $table = 'media';

    protected $fillable = [
        'tmdb_id',
        'type',
        'name',
        'original_name',
        'poster_path',
        'overview',
        'release_date',
        'first_air_date',
        'number_of_seasons',
        'number_of_episodes',
    ];

    protected $casts = [
        'release_date' => 'date',
        'first_air_date' => 'date',
    ];

    /**
     * Relationship with users through watchlists
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'watchlists')
            ->withPivot('watched')
            ->withTimestamps();
    }

    /**
     * Scope to filter only movies
     */
    public function scopeMovies($query)
    {
        return $query->where('type', 'movie');
    }

    /**
     * Scope to filter only TV shows
     */
    public function scopeTvShows($query)
    {
        return $query->where('type', 'tv');
    }

    /**
     * Get the display date (release_date for movies, first_air_date for TV)
     */
    public function getDisplayDateAttribute()
    {
        return $this->type === 'tv' ? $this->first_air_date : $this->release_date;
    }
}
