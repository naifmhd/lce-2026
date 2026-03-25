<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\CandidateController;
use App\Http\Controllers\ResultsController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VoterController;
use App\Http\Controllers\ZerodayController;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return redirect()->route('voters.index');
// })->name('home');

Route::middleware(['auth', 'verified', 'has.roles'])->group(function () {
    Route::get('/', [StatsController::class, 'index'])->name('home');
    Route::redirect('dashboard', '/')->name('dashboard');

    Route::get('voters', [VoterController::class, 'index'])->name('voters.index');
    Route::get('voters/export', [VoterController::class, 'export'])->name('voters.export');
    Route::patch('voters/{voter}', [VoterController::class, 'update'])->name('voters.update');

    Route::get('zeroday', [ZerodayController::class, 'index'])->name('zeroday.index');
    Route::patch('zeroday/{voter}/voted', [ZerodayController::class, 'markVoted'])->name('zeroday.mark-voted');

    Route::middleware('admin.role')->group(function () {
        Route::get('candidates', [CandidateController::class, 'index'])->name('candidates.index');
        Route::post('candidates', [CandidateController::class, 'store'])->name('candidates.store');
        Route::patch('candidates/{candidate}', [CandidateController::class, 'update'])->name('candidates.update');
        Route::delete('candidates/{candidate}', [CandidateController::class, 'destroy'])->name('candidates.destroy');

        Route::get('results', [ResultsController::class, 'index'])->name('results.index');
        Route::post('results/boxes', [ResultsController::class, 'storeBoxResult'])->name('results.store-box');

        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::patch('users/{user}', [UserController::class, 'update'])->name('users.update');

        Route::get('role-permissions', [RolePermissionController::class, 'index'])->name('role-permissions.index');
        Route::put('role-permissions/{role}', [RolePermissionController::class, 'update'])->name('role-permissions.update');

        Route::get('activity-log', [ActivityLogController::class, 'index'])->name('activity-log.index');
    });
});

require __DIR__.'/settings.php';
