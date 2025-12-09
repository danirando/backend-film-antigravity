<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class OmdbService
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.omdb.base_url', 'http://www.omdbapi.com/');
        $this->apiKey = config('services.omdb.api_key');
    }

    /**
     * Get movie rating from OMDb by title and optional year.
     *
     * @param string $title
     * @param int|null $year
     * @return array|null
     */
    public function getMovieRating(string $title, ?int $year = null): ?array
    {
        // Create cache key
        $cacheKey = 'omdb_rating_' . md5($title . ($year ?? ''));

        // Check cache first (cache for 30 days)
        return Cache::remember($cacheKey, 60 * 60 * 24 * 30, function () use ($title, $year) {
            $params = [
                'apikey' => $this->apiKey,
                't' => $title,
                'type' => 'movie',
            ];

            if ($year) {
                $params['y'] = $year;
            }

            try {
                $response = Http::timeout(5)->get($this->baseUrl, $params);

                if ($response->successful()) {
                    $data = $response->json();

                    // Check if movie was found
                    if (isset($data['Response']) && $data['Response'] === 'True') {
                        return [
                            'title' => $data['Title'] ?? null,
                            'year' => $data['Year'] ?? null,
                            'rated' => $data['Rated'] ?? 'N/A',
                            'imdb_rating' => $data['imdbRating'] ?? null,
                            'genre' => $data['Genre'] ?? null,
                        ];
                    }
                }

                return null;
            } catch (\Exception $e) {
                Log::warning('OMDb API Error: ' . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Check if a rating indicates adult content.
     *
     * @param string $rated
     * @return bool
     */
    public function isAdultContent(string $rated): bool
    {
        $adultRatings = [
            'NC-17',  // No one 17 and under admitted
            'X',      // Adult content (old rating)
            'NR',     // Not Rated (cautious approach)
            'UR',     // Unrated (cautious approach)
        ];

        return in_array(strtoupper($rated), $adultRatings);
    }

    /**
     * Check if content is potentially inappropriate based on rating and genre.
     *
     * @param array $omdbData
     * @return bool
     */
    public function isPotentiallyInappropriate(array $omdbData): bool
    {
        $rated = $omdbData['rated'] ?? 'N/A';
        $genre = strtolower($omdbData['genre'] ?? '');

        // Check explicit adult ratings
        if ($this->isAdultContent($rated)) {
            return true;
        }

        // Check for suspicious genre combinations with unrated content
        if (in_array($rated, ['N/A', 'Not Rated', 'Unrated'])) {
            $suspiciousGenres = ['erotic', 'adult', 'exploitation'];
            foreach ($suspiciousGenres as $suspiciousGenre) {
                if (str_contains($genre, $suspiciousGenre)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if title contains suspicious keywords indicating adult content.
     *
     * @param string $title
     * @return bool
     */
    public function hasSuspiciousKeywords(string $title): bool
    {
        $title = strtolower($title);

        $suspiciousPatterns = [
            'interstellar space',  // Common in low-budget adult films
            'erotic',
            'xxx',
            'adult only',
            'playboy',
            'penthouse',
            'bikini',
            'emmanuelle',
            'caligula',
            'showgirls',
            'strip',
            'temptation',
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (str_contains($title, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
