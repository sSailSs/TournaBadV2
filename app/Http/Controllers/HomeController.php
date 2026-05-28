<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        if (! $user) {
            return view('home');
        }

        $stats = [
            'my_tournaments' => Tournament::where('creator_id', $user->id)->count(),
        ];

        $latestTournaments = Tournament::query()
            ->where('creator_id', $user->id)
            ->latest('starts_on')
            ->take(5)
            ->get();

        return view('start', [
            'user' => $user,
            'stats' => $stats,
            'latestTournaments' => $latestTournaments,
        ]);
    }

    public function dashboard(Request $request): View
    {
        $user = $request->user();

        $tournaments = Tournament::query()
            ->where('creator_id', $user->id)
            ->withCount('players')
            ->latest('starts_on')
            ->paginate(12);

        $stats = [
            'my_tournaments' => Tournament::where('creator_id', $user->id)->count(),
        ];

        return view('dashboard', [
            'user' => $user,
            'stats' => $stats,
            'tournaments' => $tournaments,
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $user->update($validated);

        return redirect()
            ->route('dashboard')
            ->with('status', 'Profil mis a jour.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update([
            'password' => $validated['password'],
        ]);

        return redirect()
            ->route('dashboard')
            ->with('status', 'Mot de passe mis a jour.');
    }

    public function legalNotices(): View
    {
        return view('legal.mentions');
    }

    public function privacyPolicy(): View
    {
        return view('legal.confidentialite');
    }
}
