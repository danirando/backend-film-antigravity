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
    public function searchMovies(string $query, bool $includeAdult = false): array
    {
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
}
