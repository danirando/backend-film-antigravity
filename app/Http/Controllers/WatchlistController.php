<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Http\Request;

class WatchlistController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            $request->user()->media()->orderBy('watchlists.created_at', 'desc')->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tmdb_id' => 'required|integer',
            'type' => 'required|in:movie,tv',
            'name' => 'required|string',
            'original_name' => 'nullable|string',
            'poster_path' => 'nullable|string',
            'overview' => 'nullable|string',
            'release_date' => 'nullable|date',
            'first_air_date' => 'nullable|date',
            'number_of_seasons' => 'nullable|integer',
            'number_of_episodes' => 'nullable|integer',
        ]);

        // Find or create the media in local DB
        $media = Media::firstOrCreate(
            [
                'tmdb_id' => $validated['tmdb_id'],
                'type' => $validated['type']
            ],
            [
                'name' => $validated['name'],
                'original_name' => $validated['original_name'] ?? null,
                'poster_path' => $validated['poster_path'] ?? null,
                'overview' => $validated['overview'] ?? null,
                'release_date' => $validated['release_date'] ?? null,
                'first_air_date' => $validated['first_air_date'] ?? null,
                'number_of_seasons' => $validated['number_of_seasons'] ?? null,
                'number_of_episodes' => $validated['number_of_episodes'] ?? null,
            ]
        );

        // Attach to user without duplicating
        $request->user()->media()->syncWithoutDetaching([$media->id]);

        return response()->json([
            'message' => 'Media added to watchlist',
            'media' => $media
        ], 201);
    }

    public function destroy(Request $request, int $tmdbId)
    {
        $media = Media::where('tmdb_id', $tmdbId)->firstOrFail();
        
        $request->user()->media()->detach($media->id);

        return response()->json(['message' => 'Media removed from watchlist']);
    }

    public function update(Request $request, int $tmdbId)
    {
        $media = Media::where('tmdb_id', $tmdbId)->firstOrFail();
        $watched = $request->boolean('watched');

        $request->user()->media()->updateExistingPivot($media->id, [
            'watched' => $watched,
        ]);

        return response()->json(['message' => 'Watch status updated']);
    }
}
