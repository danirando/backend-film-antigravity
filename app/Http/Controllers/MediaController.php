<?php

namespace App\Http\Controllers;

use App\Services\TmdbService;
use App\Services\OmdbService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MediaController extends Controller
{
    protected TmdbService $tmdbService;
    protected OmdbService $omdbService;

    public function __construct(TmdbService $tmdbService, OmdbService $omdbService)
    {
        $this->tmdbService = $tmdbService;
        $this->omdbService = $omdbService;
    }

    /**
     * Search for movies or TV shows.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('query');
        $type = $request->input('type', 'all'); // 'movie', 'tv', or 'all'
        $includeAdult = $request->boolean('include_adult');

        if (!$query) {
            return response()->json(['error' => 'Query parameter is required'], 400);
        }

        $results = [];

        if ($type === 'movie' || $type === 'all') {
            $movieResults = $this->tmdbService->searchMovies($query, $includeAdult);
            // Add type to each result
            $movieResults = array_map(function($movie) {
                $movie['media_type'] = 'movie';
                return $movie;
            }, $movieResults);
            $results = array_merge($results, $movieResults);
        }

        if ($type === 'tv' || $type === 'all') {
            $tvResults = $this->tmdbService->searchTvShows($query, $includeAdult);
            // Add type to each result
            $tvResults = array_map(function($tv) {
                $tv['media_type'] = 'tv';
                return $tv;
            }, $tvResults);
            $results = array_merge($results, $tvResults);
        }

        // Filter adult content using OMDb validation (only for movies)
        if (!$includeAdult) {
            $results = $this->filterAdultContent($results);
        }

        // Sort by popularity (descending) when combining results
        if ($type === 'all' && count($results) > 0) {
            usort($results, function($a, $b) {
                $popularityA = $a['popularity'] ?? 0;
                $popularityB = $b['popularity'] ?? 0;
                return $popularityB <=> $popularityA; // Descending order
            });
        }

        return response()->json($results);
    }

    /**
     * Filter adult content from results using OMDb.
     *
     * @param array $results
     * @return array
     */
    /**
     * Filter adult content from results using OMDb.
     *
     * @param array $results
     * @return array
     */
    protected function filterAdultContent(array $results): array
    {
        $moviesToCheck = [];
        $indicesToCheck = [];

        // First pass: identify movies that need OMDb verification
        foreach ($results as $index => $item) {
            // Only validate movies
            if (($item['media_type'] ?? 'movie') !== 'movie') {
                continue;
            }

            $title = $item['title'] ?? $item['name'] ?? '';
            
            // First check: suspicious keywords in title (fast check)
            if ($this->omdbService->hasSuspiciousKeywords($title)) {
                unset($results[$index]); // Remove immediately
                continue;
            }

            $year = null;
            if (isset($item['release_date']) && !empty($item['release_date'])) {
                $year = (int) substr($item['release_date'], 0, 4);
            }

            $moviesToCheck[] = [
                'title' => $title,
                'year' => $year,
                'id' => $index // Use array index as ID to map back
            ];
            $indicesToCheck[] = $index;
        }

        if (empty($moviesToCheck)) {
            return array_values($results);
        }

        // Batch fetch OMDb ratings
        $ratings = $this->omdbService->getMovieRatingsMultiple($moviesToCheck);

        // Second pass: filter based on OMDb data
        foreach ($indicesToCheck as $index) {
            $omdbData = $ratings[$index] ?? null;

            // If OMDb doesn't have data, we already checked keywords, so keep it
            if (!$omdbData) {
                continue;
            }

            // Filter out if inappropriate based on OMDb data
            if ($this->omdbService->isPotentiallyInappropriate($omdbData)) {
                unset($results[$index]);
            }
        }

        return array_values($results);
    }

    /**
     * Get movie or TV show details.
     *
     * @param string $type
     * @param int $id
     * @return JsonResponse
     */
    public function show(string $type, int $id): JsonResponse
    {
        if ($type === 'movie') {
            $details = $this->tmdbService->getMovieDetails($id);
        } elseif ($type === 'tv') {
            $details = $this->tmdbService->getTvShowDetails($id);
        } else {
            return response()->json(['error' => 'Invalid type. Use "movie" or "tv"'], 400);
        }

        if (!$details) {
            return response()->json(['error' => 'Media not found'], 404);
        }

        $details['media_type'] = $type;
        return response()->json($details);
    }

    /**
     * Get autocomplete suggestions.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function suggestions(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        
        $results = $this->tmdbService->getTrending($query);
        
        // Format for autocomplete
        $suggestions = array_map(function($item) {
            return [
                'id' => $item['id'],
                'title' => $item['title'] ?? $item['name'] ?? 'Untitled',
                'media_type' => $item['media_type'] ?? 'movie',
                'year' => isset($item['release_date']) ? substr($item['release_date'], 0, 4) : 
                         (isset($item['first_air_date']) ? substr($item['first_air_date'], 0, 4) : ''),
            ];
        }, $results);

        return response()->json($suggestions);
    }

    /**
     * Get popular TV shows.
     *
     * @return JsonResponse
     */
    public function popularTv(): JsonResponse
    {
        $results = $this->tmdbService->getPopularTvShows();
        
        // Add type to each result
        $results = array_map(function($tv) {
            $tv['media_type'] = 'tv';
            return $tv;
        }, $results);

        return response()->json($results);
    }
}
