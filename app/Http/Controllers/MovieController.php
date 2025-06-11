<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\Genre;
use App\Services\TmdbService;
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

        $query = Movie::query()->orderBy('title');
        
        // Apply genre filter if selected
        if ($genre = request()->input('genre')) {
            $query->whereHas('genres', function($q) use ($genre) {
                $q->where('genres.id', $genre);
            });
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

        $genres = Genre::orderBy('name')->get();

        // Get a separate query for carousel movies (most recent 5)
        $carouselMovies = Movie::orderBy('created_at', 'desc')
            ->take(5)
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
            });

        return Inertia::render('Dashboard', [
            'movies' => $movies,
            'genres' => $genres,
            'carouselMovies' => $carouselMovies,
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
            // Extract year if present (assuming format includes a year like "Movie Name 2023")
            $year = null;
            if (preg_match('/\b(\d{4})\b/', $filename, $matches)) {
                $year = (int)$matches[1];
            }
            
            // Clean up filename for better search results
            $searchTitle = preg_replace('/[\._\-\(\)]/', ' ', $filename);
            $searchTitle = preg_replace('/\s*\d{4}\s*/', ' ', $searchTitle);
            $searchTitle = trim(preg_replace('/\s+/', ' ', $searchTitle));
            
            Log::info('Processing movie:', [
                'filename' => $filename, 
                'searchTitle' => $searchTitle,
                'year' => $year
            ]);
            
            // Skip if movie already exists
            if (Movie::where('local_path', $file)->exists()) {
                Log::info('Movie already exists, skipping', ['file' => $file]);
                continue;
            }

            Log::info('Searching TMDB for movie:', ['searchTitle' => $searchTitle, 'year' => $year]);
            $searchResult = $this->tmdbService->searchMovie($searchTitle, $year);
            Log::info('TMDB search result:', ['result' => $searchResult]);
            
            if (!empty($searchResult['results'])) {
                $movieData = $searchResult['results'][0];
                $details = $this->tmdbService->getMovieDetails($movieData['id']);
                
                // Movie details already includes the trailer URL
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
            }
        }

        return redirect()->route('dashboard');
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

            $path = Storage::disk('public')->path($movie->local_path);
            $fileSize = filesize($path);
            $file = fopen($path, 'rb');

            // Handle range requests for seeking
            $start = 0;
            $end = $fileSize - 1;
            $status = 200;

            if (isset($_SERVER['HTTP_RANGE'])) {
                preg_match('/bytes=(\d+)-(\d+)?/', $_SERVER['HTTP_RANGE'], $matches);
                $start = intval($matches[1]);
                $end = isset($matches[2]) ? intval($matches[2]) : $fileSize - 1;
                $status = 206;
            }

            $length = $end - $start + 1;

            // Set headers to prevent download and enforce streaming
            header('Content-Type: video/mp4');
            header('Accept-Ranges: bytes');
            header("Content-Length: $length");
            if ($status == 206) {
                header("Content-Range: bytes $start-$end/$fileSize");
            }
            header('Cache-Control: no-store, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');
            header('X-Content-Type-Options: nosniff');
            header('Content-Security-Policy: default-src \'self\'');
            header('X-Frame-Options: SAMEORIGIN');
            header('Content-Disposition: inline');

            http_response_code($status);
            fseek($file, $start);

            $buffer = 8192;
            $currentPosition = $start;

            while (!feof($file) && $currentPosition <= $end) {
                if (connection_status() != CONNECTION_NORMAL) {
                    fclose($file);
                    exit;
                }

                $bufferSize = min($buffer, $end - $currentPosition + 1);
                echo fread($file, $bufferSize);
                $currentPosition += $bufferSize;
                flush();
                ob_flush();
                usleep(1000);
            }

            fclose($file);
            exit;

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
                'expires' => now()->addSeconds(10)->timestamp // Shorter expiry
            ],
            'user_id' => auth()->id() // Add user ID to token
        ]);
        
        return response()->json([
            'token' => $token,
            'url' => route('movies.stream', ['token' => $token])
        ]);
    }

    
    
   
   


}
