<?php

namespace App\Http\Controllers;

use App\Services\TmdbService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    protected TmdbService $tmdbService;

    public function __construct(TmdbService $tmdbService)
    {
        $this->tmdbService = $tmdbService;
    }

    /**
     * Get personalized recommendations based on user's watchlist.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function forYou(Request $request)
    {
        $mediaType = $request->query('media_type', 'movie');
        $user = $request->user();

        // Get user's watchlist
        $watchlist = $user->media()
            ->where('type', $mediaType)
            ->orderBy('watchlists.created_at', 'desc')
            ->limit(10)
            ->get();

        if ($watchlist->isEmpty()) {
            return response()->json([]);
        }

        $recommendations = [];
        $seenIds = [];

        // Collect IDs from watchlist to exclude them
        $watchlistIds = $watchlist->pluck('tmdb_id')->toArray();

        // Get recommendations for each item in watchlist
        foreach ($watchlist as $media) {
            $tmdbRecommendations = $this->tmdbService->getRecommendations($mediaType, $media->tmdb_id);
            
            foreach ($tmdbRecommendations as $rec) {
                $recId = $rec['id'];
                
                // Skip if already in watchlist or already added
                if (in_array($recId, $watchlistIds) || in_array($recId, $seenIds)) {
                    continue;
                }
                
                $seenIds[] = $recId;
                $recommendations[] = $rec;
                
                // Limit to 30 recommendations
                if (count($recommendations) >= 30) {
                    break 2;
                }
            }
        }

        // Sort by popularity
        usort($recommendations, function($a, $b) {
            return ($b['popularity'] ?? 0) <=> ($a['popularity'] ?? 0);
        });

        return response()->json(array_slice($recommendations, 0, 20));
    }

    /**
     * Get movies now playing in theaters.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function nowPlaying(Request $request)
    {
        $region = $request->query('region', 'IT');
        $movies = $this->tmdbService->getNowPlaying($region);

        return response()->json($movies);
    }

    /**
     * Get trending content.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function trending(Request $request)
    {
        $mediaType = $request->query('media_type', 'movie');
        $timeWindow = $request->query('time_window', 'week');
        
        $content = $this->tmdbService->getTrendingContent($mediaType, $timeWindow);

        return response()->json($content);
    }

    /**
     * Get popular content.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function popular(Request $request)
    {
        $mediaType = $request->query('media_type', 'movie');
        
        if ($mediaType === 'tv') {
            $content = $this->tmdbService->getPopularTvShows();
        } else {
            $content = $this->tmdbService->getPopularMovies();
        }

        return response()->json($content);
    }
}
