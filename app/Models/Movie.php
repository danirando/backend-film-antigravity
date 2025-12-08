<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    use HasFactory;

    protected $fillable = [
        'tmdb_id',
        'title',
        'poster_path',
        'release_date',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'watchlists')
            ->withPivot('watched')
            ->withTimestamps();
    }
}
