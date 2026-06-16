<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\TournamentController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/mentions-legales', [HomeController::class, 'legalNotices'])->name('legal.notices');
Route::get('/confidentialite', [HomeController::class, 'privacyPolicy'])->name('legal.privacy');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [HomeController::class, 'dashboard'])->name('dashboard');
    Route::get('/dashboard/tournaments', [HomeController::class, 'dashboardTournaments'])->name('dashboard.tournaments');
    Route::patch('/dashboard/profile', [HomeController::class, 'updateProfile'])->name('dashboard.profile.update');
    Route::patch('/dashboard/password', [HomeController::class, 'updatePassword'])->name('dashboard.password.update');

    Route::get('/tournaments', [TournamentController::class, 'index'])->name('tournaments.index');
    Route::get('/tournaments/create', [TournamentController::class, 'create'])->name('tournaments.create');
    Route::post('/tournaments', [TournamentController::class, 'store'])->name('tournaments.store');
    Route::delete('/tournaments/{tournament}', [TournamentController::class, 'destroy'])->name('tournaments.destroy');
    Route::get('/tournaments/{tournament}', [TournamentController::class, 'show'])->name('tournaments.show');
    Route::get('/tournaments/{tournament}/settings', [TournamentController::class, 'settings'])->name('tournaments.settings');
    Route::patch('/tournaments/{tournament}/settings', [TournamentController::class, 'updateSettings'])->name('tournaments.settings.update');
    Route::post('/tournaments/{tournament}/rounds/generate', [TournamentController::class, 'generateRound'])->name('tournaments.rounds.generate');
    Route::delete('/tournaments/{tournament}/rounds/{round}', [TournamentController::class, 'removeRound'])->name('tournaments.rounds.destroy');
    Route::post('/tournaments/{tournament}/matches/{match}/score', [TournamentController::class, 'recordMatchScore'])->name('tournaments.matches.score');
    Route::get('/tournaments/{tournament}/points', [TournamentController::class, 'points'])->name('tournaments.points');
    Route::get('/tournaments/{tournament}/final', [TournamentController::class, 'final'])->name('tournaments.final');
    Route::post('/tournaments/{tournament}/reset', [TournamentController::class, 'reset'])->name('tournaments.reset');

    Route::get('/tournaments/{tournament}/players', [TournamentController::class, 'players'])->name('tournaments.players');
    Route::post('/tournaments/{tournament}/players', [TournamentController::class, 'addPlayer'])->name('tournaments.players.store');
    Route::patch('/tournaments/{tournament}/players/{player}/points', [TournamentController::class, 'updatePlayerPoints'])->name('tournaments.players.points.update');
    Route::delete('/tournaments/{tournament}/players/{player}', [TournamentController::class, 'removePlayer'])->name('tournaments.players.destroy');

    Route::get('/tournaments/{tournament}/teams', [TournamentController::class, 'teams'])->name('tournaments.teams');
    Route::patch('/tournaments/{tournament}/teams/display', [TournamentController::class, 'updateTeamDisplay'])->name('tournaments.teams.display.update');
    Route::post('/tournaments/{tournament}/teams', [TournamentController::class, 'addTeam'])->name('tournaments.teams.store');
    Route::patch('/tournaments/{tournament}/teams/{team}', [TournamentController::class, 'updateTeam'])->name('tournaments.teams.update');
    Route::delete('/tournaments/{tournament}/teams/{team}', [TournamentController::class, 'removeTeam'])->name('tournaments.teams.destroy');
});
