<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        return view('dashboard', [
            'stats' => [
                'notes' => $user->notes()->count(),
                'categories' => $user->categories()->count(),
                'favorites' => $user->notes()->where('is_favorite', true)->count(),
                'conversations' => $user->conversations()->count(),
            ],
            'pinned' => $user->notes()->where('is_pinned', true)->latest('updated_at')->take(4)->get(),
            'recent' => $user->notes()->with('category')->latest('updated_at')->take(6)->get(),
            'inbox' => $user->notes()->where('note_type', 'inbox')->count(),
        ]);
    }
}
