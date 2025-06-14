<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Movie;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public function index()
    {
        $movies = Movie::with('genres')
            ->orderBy('created_at', 'desc')
            ->paginate(5);

        $users = User::select('users.*')
            ->leftJoin('sessions', 'users.id', '=', 'sessions.user_id')
            ->selectRaw('MAX(sessions.last_activity) as last_login_at')
            ->groupBy(
                'users.id', 
                'users.username', 
                'users.password', 
                'users.is_admin', 
                'users.remember_token', 
                'users.created_at', 
                'users.updated_at'
            )
            ->paginate(5)
            ->through(function ($user) {
                $user->last_login_at = $user->last_login_at ? date('Y-m-d H:i:s', $user->last_login_at) : null;
                return $user;
            });

        return Inertia::render('Admin/Dashboard', [
            'movies' => $movies,
            'users' => $users,
        ]);
    }
}
