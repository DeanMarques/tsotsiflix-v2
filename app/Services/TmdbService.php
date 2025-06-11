<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TmdbService
{
    private string $baseUrl = 'https://api.themoviedb.org/3';
    private string $token;

    public function __construct()
    {
        $this->token = config('services.tmdb.token');
    }

    public function searchMovie(string $title, ?int $year = null)
    {
        $cacheKey = 'tmdb_search_' . md5($title . ($year ? "_$year" : ''));
        
        return Cache::remember($cacheKey, now()->addDays(7), function () use ($title, $year) {
            $params = [
                'query' => $title,
                'include_adult' => false,
                'language' => 'en-US',
            ];

            if ($year) {
                $params['year'] = $year;
            }

            $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->token,
                    'accept' => 'application/json'
                ])->get($this->baseUrl . '/search/movie', $params);
                
            if (!$response->successful()) {
                Log::error('TMDB API error:', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return ['results' => []];
            }

            return $response->json();
        });
    }

    public function getMovieDetails(int $movieId)
    {
        $cacheKey = 'tmdb_movie_' . $movieId;
        
        return Cache::remember($cacheKey, now()->addDays(7), function () use ($movieId) {
            // Get movie details
            // Get basic movie details including runtime
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'accept' => 'application/json'
            ])->get($this->baseUrl . '/movie/' . $movieId);

            if (!$response->successful()) {
                return null;
            }

            $movieData = $response->json();
            
            // Add runtime from the movie details response
            $movieData['runtime'] = $movieData['runtime'] ?? null;

            // Get credits for director and cast
            $creditsResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'accept' => 'application/json'
            ])->get($this->baseUrl . '/movie/' . $movieId . '/credits');

            if ($creditsResponse->successful()) {
                $credits = $creditsResponse->json();
                
                // Get director
                $director = collect($credits['crew'] ?? [])->first(function ($crewMember) {
                    return $crewMember['job'] === 'Director';
                });
                
                // Get top cast members (first 5)
                $cast = collect($credits['cast'] ?? [])
                    ->take(5)
                    ->map(function ($castMember) {
                        return [
                            'name' => $castMember['name'],
                            'character' => $castMember['character'],
                            'profile_path' => $castMember['profile_path']
                        ];
                    })
                    ->toArray();

                $movieData['director'] = $director ? $director['name'] : null;
                $movieData['cast'] = $cast;
            }

            // Get videos in the same call
            $videosResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'accept' => 'application/json'
            ])->get($this->baseUrl . '/movie/' . $movieId . '/videos');

            if ($videosResponse->successful()) {
                $videos = $videosResponse->json()['results'] ?? [];
                
                // Find official trailer
                $trailer = collect($videos)->first(function ($video) {
                    return $video['type'] === 'Trailer' && 
                           $video['site'] === 'YouTube' && 
                           ($video['official'] ?? false);
                });

                // If no official trailer, get any trailer
                if (!$trailer) {
                    $trailer = collect($videos)->first(function ($video) {
                        return $video['type'] === 'Trailer' && 
                               $video['site'] === 'YouTube';
                    });
                }

                // Add trailer URL to movie data
                $movieData['trailer_url'] = $trailer && isset($trailer['key']) 
                    ? "https://www.youtube.com/embed/{$trailer['key']}" 
                    : null;
            }

            return $movieData;
        });
    }

    public function getGenres()
    {
        $cacheKey = 'tmdb_genres';
        
        return Cache::remember($cacheKey, now()->addDays(30), function () {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'accept' => 'application/json'
            ])->get($this->baseUrl . '/genre/movie/list');

            if (!$response->successful()) {
                Log::error('Failed to fetch genres from TMDB:', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return [];
            }

            return $response->json()['genres'] ?? [];
        });
    }
}
