<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Watched extends Model
{
    protected $table = 'watched';

    protected $fillable = [
        'user_id',
        'movie_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function movie()
    {
        return $this->belongsTo(Movie::class);
    }
}