<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TmdbService
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.tmdb.base_url', 'https://api.themoviedb.org/3');
        $this->apiKey = config('services.tmdb.api_key');
    }

    /**
     * Cerca film per titolo.
     *
     * @param string $query
     * @return array
     */
    /**
     * Cerca film per titolo.
     *
     * @param string $query
     * @return array
     */
    public function searchMovies(string $query, bool $includeAdult = false): array
    {
        $cacheKey = 'tmdb_search_movie_' . md5($query . ($includeAdult ? '_adult' : ''));

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 60 * 60, function () use ($query, $includeAdult) {
            $response = Http::get("{$this->baseUrl}/search/movie", [
                'api_key' => $this->apiKey,
                'query' => $query,
                'language' => 'it-IT',
                'include_adult' => $includeAdult,
            ]);

            if ($response->successful()) {
                return $response->json()['results'] ?? [];
            }

            Log::error('TMDB Search Error: ' . $response->body());
            return [];
        });
    }

    /**
     * Cerca serie TV per titolo.
     *
     * @param string $query
     * @param bool $includeAdult
     * @return array
     */
    public function searchTvShows(string $query, bool $includeAdult = false): array
    {
        $cacheKey = 'tmdb_search_tv_' . md5($query . ($includeAdult ? '_adult' : ''));

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 60 * 60, function () use ($query, $includeAdult) {
            $response = Http::get("{$this->baseUrl}/search/tv", [
                'api_key' => $this->apiKey,
                'query' => $query,
                'language' => 'it-IT',
                'include_adult' => $includeAdult,
            ]);

            if ($response->successful()) {
                return $response->json()['results'] ?? [];
            }

            Log::error('TMDB TV Search Error: ' . $response->body());
            return [];
        });
    }

    /**
     * Ottieni dettagli di un film per ID.
     *
     * @param int $id
     * @return array|null
     */
    public function getMovieDetails(int $id): ?array
    {
        $response = Http::get("{$this->baseUrl}/movie/{$id}", [
            'api_key' => $this->apiKey,
            'language' => 'it-IT',
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('TMDB Detail Error: ' . $response->body());
        return null;
    }

    /**
     * Ottieni dettagli di una serie TV per ID.
     *
     * @param int $id
     * @return array|null
     */
    public function getTvShowDetails(int $id): ?array
    {
        $response = Http::get("{$this->baseUrl}/tv/{$id}", [
            'api_key' => $this->apiKey,
            'language' => 'it-IT',
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('TMDB TV Detail Error: ' . $response->body());
        return null;
    }

    /**
     * Ottieni serie TV popolari.
     *
     * @return array
     */
    public function getPopularTvShows(): array
    {
        $response = Http::get("{$this->baseUrl}/tv/popular", [
            'api_key' => $this->apiKey,
            'language' => 'it-IT',
        ]);

        if ($response->successful()) {
            return $response->json()['results'] ?? [];
        }

        Log::error('TMDB Popular TV Error: ' . $response->body());
        return [];
    }

    /**
     * Get trending content for autocomplete suggestions.
     *
     * @param string $query
     * @return array
     */
    public function getTrending(string $query = ''): array
    {
        // If query is provided, search multi; otherwise get trending
        if (!empty($query)) {
            $cacheKey = 'tmdb_suggestions_' . md5($query);
            
            return \Illuminate\Support\Facades\Cache::remember($cacheKey, 60 * 5, function () use ($query) {
                $response = Http::get("{$this->baseUrl}/search/multi", [
                    'api_key' => $this->apiKey,
                    'query' => $query,
                    'language' => 'it-IT',
                    'include_adult' => false,
                    'page' => 1,
                ]);

                if ($response->successful()) {
                    $results = $response->json()['results'] ?? [];
                    // Filter to only movies and TV shows
                    $filtered = array_filter($results, fn($item) => in_array($item['media_type'] ?? '', ['movie', 'tv']));
                    
                    // Sort by popularity (descending)
                    usort($filtered, function($a, $b) {
                        return ($b['popularity'] ?? 0) <=> ($a['popularity'] ?? 0);
                    });
                    
                    return array_slice($filtered, 0, 8);
                }

                return [];
            });
        }

        // Get trending content
        $cacheKey = 'tmdb_trending_all';
        
        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 60 * 60, function () {
            $response = Http::get("{$this->baseUrl}/trending/all/week", [
                'api_key' => $this->apiKey,
                'language' => 'it-IT',
            ]);

            if ($response->successful()) {
                $results = $response->json()['results'] ?? [];
                return array_slice($results, 0, 8);
            }

            return [];
        });
    }
}
