<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VoterController;
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

    Route::middleware('admin.role')->group(function () {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::patch('users/{user}', [UserController::class, 'update'])->name('users.update');

        Route::get('role-permissions', [RolePermissionController::class, 'index'])->name('role-permissions.index');
        Route::put('role-permissions/{role}', [RolePermissionController::class, 'update'])->name('role-permissions.update');

        Route::get('activity-log', [ActivityLogController::class, 'index'])->name('activity-log.index');
    });
});

require __DIR__.'/settings.php';
