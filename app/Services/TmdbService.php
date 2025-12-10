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
}
