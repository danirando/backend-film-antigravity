<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Http\Request;

class WatchlistController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            $request->user()->movies()->orderBy('watchlists.created_at', 'desc')->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tmdb_id' => 'required|integer',
            'title' => 'required|string',
            'poster_path' => 'nullable|string',
            'release_date' => 'nullable|date',
        ]);

        // Find or create the movie in local DB
        $movie = Movie::firstOrCreate(
            ['tmdb_id' => $validated['tmdb_id']],
            [
                'title' => $validated['title'],
                'poster_path' => $validated['poster_path'],
                'release_date' => $validated['release_date'],
            ]
        );

        // Attach to user without duplicating
        $request->user()->movies()->syncWithoutDetaching([$movie->id]);

        return response()->json([
            'message' => 'Movie added to watchlist',
            'movie' => $movie
        ], 201);
    }

    public function destroy(Request $request, int $tmdbId)
    {
        $movie = Movie::where('tmdb_id', $tmdbId)->firstOrFail();
        
        $request->user()->movies()->detach($movie->id);

        return response()->json(['message' => 'Movie removed from watchlist']);
    }

    public function update(Request $request, int $tmdbId)
    {
        $movie = Movie::where('tmdb_id', $tmdbId)->firstOrFail();
        $watched = $request->boolean('watched');

        $request->user()->movies()->updateExistingPivot($movie->id, [
            'watched' => $watched,
        ]);

        return response()->json(['message' => 'Watch status updated']);
    }
}
