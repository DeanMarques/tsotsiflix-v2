<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\Genre;
use App\Services\TmdbService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Inertia\Inertia;


class MovieController extends Controller
{
    public function __construct(
        private TmdbService $tmdbService
    ) {}

    public function dashboard()
    {
        $page = request()->input('page', 1);
        $perPage = 6; // Load 6 movies at a time for smoother infinite scroll
        $currentGenre = request()->input('genre');
        $search = request()->input('search');

        $query = Movie::query()->orderBy('release_date', 'desc');
        
        // Apply genre filter if selected
        if ($currentGenre) {
            $query->whereHas('genres', function($q) use ($currentGenre) {
                $q->where('genres.id', $currentGenre);
            });
        }

        // Apply search if provided
        if ($search) {
            $query->where('title', 'like', '%' . $search . '%');
        }

        $movies = $query->paginate($perPage)
            ->through(function ($movie) {
                return [
                    'id' => $movie->id,
                    'tmdb_id' => $movie->tmdb_id,
                    'title' => $movie->title,
                    'overview' => $movie->overview,
                    'poster_path' => $movie->poster_path 
                        ? "https://image.tmdb.org/t/p/w500" . $movie->poster_path 
                        : null,
                    'backdrop_path' => $movie->backdrop_path 
                        ? "https://image.tmdb.org/t/p/original" . $movie->backdrop_path 
                        : null,
                    'release_date' => $movie->release_date,
                    'vote_average' => $movie->vote_average,
                    'local_path' => $movie->local_path,
                    'trailer_url' => $movie->trailer_url,
                    'director' => $movie->director,
                    'cast' => $movie->cast,
                    'runtime' => $movie->runtime,
                    'genres' => $movie->genres->map(fn($genre) => [
                        'id' => $genre->id,
                        'name' => $genre->name
                    ]),
                ];
             });

        // Only get carousel movies if no search or genre filter
        $carouselMovies = (!$search && !$currentGenre) 
            ? Movie::orderBy('release_date', 'desc')
                ->take(10)
                ->get()
                ->map(function ($movie) {
                    return [
                        'id' => $movie->id,
                        'tmdb_id' => $movie->tmdb_id,
                        'title' => $movie->title,
                        'overview' => $movie->overview,
                        'poster_path' => $movie->poster_path 
                            ? "https://image.tmdb.org/t/p/w500" . $movie->poster_path 
                            : null,
                        'backdrop_path' => $movie->backdrop_path 
                            ? "https://image.tmdb.org/t/p/original" . $movie->backdrop_path 
                            : null,
                        'release_date' => $movie->release_date,
                        'vote_average' => $movie->vote_average,
                        'local_path' => $movie->local_path,
                        'trailer_url' => $movie->trailer_url,
                        'director' => $movie->director,
                        'cast' => $movie->cast,
                        'runtime' => $movie->runtime,
                        'genres' => $movie->genres->map(fn($genre) => [
                            'id' => $genre->id,
                            'name' => $genre->name
                        ]),
                    ];
                })
            : [];

        return Inertia::render('Movies/Dashboard', [
            'movies' => $movies,
            'genres' => Genre::orderBy('name')->get(),
            'carouselMovies' => $carouselMovies,
            'currentGenre' => $currentGenre,
            'search' => $search
        ]);
    }

    
 
    public function scanMovies()
    {
        Log::info('Starting movie scan');
        
        // Sync genres first
        $this->syncGenres();
        $files = Storage::disk('public')->files('movies');
        Log::info('Found files:', ['files' => $files]);
        
        $movieFiles = array_filter($files, function($file) {
            return pathinfo($file, PATHINFO_EXTENSION) === 'mp4';
        });
        Log::info('Filtered MP4 files:', ['movieFiles' => $movieFiles]);

        foreach ($movieFiles as $file) {
            $filename = pathinfo($file, PATHINFO_FILENAME);
            
            // Extract year if present
            $year = null;
            if (preg_match('/\((\d{4})\)/', $filename, $matches)) {
                $year = (int)$matches[1];
            }
            
            // Improved title cleaning
            $searchTitle = preg_replace('/[\._\-\(\)]/', ' ', $filename);
            $searchTitle = preg_replace('/\s*\d{4}\s*/', ' ', $searchTitle);
            $searchTitle = trim(preg_replace('/\s+/', ' ', $searchTitle));
            
            Log::info('Processing movie:', [
                'original_filename' => $filename, 
                'cleaned_title' => $searchTitle,
                'year' => $year
            ]);
            
            if (Movie::where('local_path', $file)->exists()) {
                Log::info('Movie already exists, skipping', ['file' => $file]);
                continue;
            }

            $searchResult = $this->tmdbService->searchMovie($searchTitle, $year);
            
            if (!empty($searchResult['results'])) {
                // Enhanced filtering logic
                $movieResults = collect($searchResult['results'])
                    ->filter(function($result) use ($year, $searchTitle) {
                        // Must match year exactly if provided
                        if ($year) {
                            $releaseYear = date('Y', strtotime($result['release_date']));
                            if ((int)$releaseYear !== $year) {
                                return false;
                            }
                        }
                        
                        // Compare titles more strictly
                        $resultTitle = strtolower(trim($result['title']));
                        $searchTitle = strtolower(trim($searchTitle));
                        
                        // Exact match gets highest priority
                        if ($resultTitle === $searchTitle) {
                            return true;
                        }
                        
                        // Check if search title is contained within result title
                        if (strpos($resultTitle, $searchTitle) !== false) {
                            return true;
                        }
                        
                        // Calculate similarity percentage
                        similar_text($resultTitle, $searchTitle, $percent);
                        return $percent > 80; // Require 80% similarity
                    })
                    ->sortBy(function($result) use ($searchTitle) {
                        // Sort by title similarity
                        similar_text(
                            strtolower($result['title']), 
                            strtolower($searchTitle), 
                            $percent
                        );
                        return -$percent; // Negative to sort highest first
                    })
                    ->values();

                if ($movieResults->isNotEmpty()) {
                    $movieData = $movieResults->first();
                    
                    // Log match details for verification
                    Log::info('Movie match found', [
                        'search_title' => $searchTitle,
                        'matched_title' => $movieData['title'],
                        'year' => $year,
                        'release_date' => $movieData['release_date']
                    ]);

                    // Check for duplicate TMDB ID
                    if (!Movie::where('tmdb_id', $movieData['id'])->exists()) {
                        $details = $this->tmdbService->getMovieDetails($movieData['id']);
                        
                        $movie = Movie::create([
                            'title' => $movieData['title'],
                            'tmdb_id' => $movieData['id'],
                            'overview' => $movieData['overview'],
                            'poster_path' => $movieData['poster_path'],
                            'backdrop_path' => $movieData['backdrop_path'],
                            'release_date' => $movieData['release_date'],
                            'vote_average' => $movieData['vote_average'],
                            'local_path' => $file,
                            'trailer_url' => $details['trailer_url'] ?? null,
                            'director' => $details['director'] ?? null,
                            'cast' => $details['cast'] ?? [],
                            'runtime' => $details['runtime'] ?? null,
                        ]);

                        // Sync genres
                        if (!empty($movieData['genre_ids'])) {
                            $genreIds = Genre::whereIn('tmdb_id', $movieData['genre_ids'])
                                ->pluck('id');
                            $movie->genres()->sync($genreIds);
                        }
                    } else {
                        Log::info('Movie with TMDB ID already exists', [
                            'tmdb_id' => $movieData['id'],
                            'title' => $movieData['title']
                        ]);
                    }
                } else {
                    Log::warning('No matching movie found for year', [
                        'title' => $searchTitle,
                        'year' => $year
                    ]);
                }
            }
        }

        return redirect()->route('dashboard');
    }

    
    public function moveMovies()
    {
        try {
            $currentUser = exec('whoami');
            Log::info('Current user:', ['user' => $currentUser]);

            $sourceDir = '/home/forge/downloads/complete/';
            $destinationDir = '/mnt/usb/tsotsiflix/mediafiles/movies/';

            $movieDirs = array_filter(glob($sourceDir . '*'), 'is_dir');

            foreach ($movieDirs as $movieDir) {
                $folderName = basename($movieDir);
                $mp4Files = glob($movieDir . '/*.mp4');
                
                if (!empty($mp4Files)) {
                    $sourceFile = $mp4Files[0];
                    $newFilename = $folderName . '.mp4';
                    $destinationFile = $destinationDir . $newFilename;

                    if (copy($sourceFile, $destinationFile)) {
                        $command = 'rm -rf ' . escapeshellarg($movieDir);
                        Log::info("Executing command: " . $command);
                        
                        // Initialize variables before exec call
                        $output = [];
                        $returnValue = null;
                        
                        // Pass variables by reference to exec
                        exec($command . ' 2>&1', $output, $returnValue);
                        
                        if ($returnValue !== 0) {
                            Log::error("Delete command failed", [
                                'command' => $command,
                                'return_value' => $returnValue,
                                'output' => $output
                            ]);
                        } else {
                            Log::info("Successfully processed: {$folderName}");
                        }
                    }
                }
            }

            return response()->json(['message' => 'Movie files processed successfully']);
        } catch (\Exception $e) {
            Log::error('Error moving movies: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }




    private function syncGenres()
    {
        $genres = $this->tmdbService->getGenres();
        foreach ($genres as $genre) {
            Genre::updateOrCreate(
                ['tmdb_id' => $genre['id']],
                ['name' => $genre['name']]
            );
        }
    }
    
    public function streamVideo($token)
    {
        try {
            // Validate referer to ensure request comes from our site
            $referer = request()->headers->get('referer');
            if (!$referer || !str_contains($referer, request()->getHost())) {
                Log::error('Invalid referer for video stream', [
                    'referer' => $referer,
                    'host' => request()->getHost()
                ]);
                throw new \Symfony\Component\HttpKernel\Exception\HttpException(403, 'Invalid request origin');
            }

            $data = decrypt($token);
            
            $movieId = $data['movie_id']['id'] ?? null;
            $expires = $data['movie_id']['expires'] ?? null;
            $userId = $data['user_id'] ?? null;
            
            if (!$movieId || !$expires || !$userId || 
                now()->timestamp > $expires || 
                $userId !== auth()->id()) {
                abort(401, 'Invalid or expired token');
            }
            
            $movie = Movie::findOrFail($movieId);
            
            if (!Storage::disk('public')->exists($movie->local_path)) {
                abort(404, 'Video file not found');
            }

            // Let Nginx handle the actual streaming
            return response()->file(Storage::disk('public')->path($movie->local_path));
        } catch (\Exception $e) {
            Log::error('Video streaming error: ' . $e->getMessage());
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                abort($e->getStatusCode(), $e->getMessage());
            }
            abort(401, $e->getMessage());
        }
    }
           
   
    public function getSecureVideoUrl($id)
    {
        $movie = Movie::findOrFail($id);
        
        $token = encrypt([
            'movie_id' => [
                'id' => $movie->id,
                'expires' => now()->addMinutes(35)->timestamp // Shorter expiry
            ],
            'user_id' => auth()->id() // Add user ID to token
        ]);
        
        return response()->json([
            'token' => $token,
            'url' => route('movies.stream', ['token' => $token])
        ]);
    }

    
    
   
   


}
