<?php

use App\Http\Controllers\StatsController;
use App\Http\Controllers\VoterController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return redirect()->route('voters.index');
})->name('home');

Route::get('dashboard', function () {
    // return Inertia::render('Dashboard');
    return redirect()->route('voters.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('voters', [VoterController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('voters.index');

Route::patch('voters/{voter}', [VoterController::class, 'update'])
    ->middleware(['auth', 'verified'])
    ->name('voters.update');

Route::get('stats', [StatsController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('stats.index');

require __DIR__.'/settings.php';
