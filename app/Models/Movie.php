<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    protected $fillable = [
        'title',
        'tmdb_id',
        'overview',
        'poster_path',
        'backdrop_path',
        'release_date',
        'vote_average',
        'local_path',
        'trailer_url',
        'director',
        'cast',
        'runtime'
    ];

    protected $casts = [
        'release_date' => 'date',
        'vote_average' => 'float',
        'cast' => 'array',
        'runtime' => 'integer',
    ];

    public function genres()
    {
        return $this->belongsToMany(Genre::class);
    }
}
