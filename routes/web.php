<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InsuranceContractController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\ProposalReviewController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VehicleLookupController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');
Route::middleware('auth')->group(function () {
    Route::post('/vehicles/lookup', VehicleLookupController::class)->middleware(['can:create,App\\Models\\Vehicle', 'throttle:vehicle-lookup'])->name('vehicles.lookup');
    Route::resource('vehicles', VehicleController::class);
    Route::resource('plans', PlanController::class);
    Route::resource('proposals', ProposalController::class);
    Route::get('/contracts', [InsuranceContractController::class, 'index'])->name('contracts.index');
    Route::get('/contracts/{contract}', [InsuranceContractController::class, 'show'])->name('contracts.show');
    Route::post('/proposals/{proposal}/confirm', [InsuranceContractController::class, 'confirm'])->name('proposals.confirm');
    Route::middleware('role:corretor,administrador')->group(function () {
        Route::get('/proposal-reviews', [ProposalReviewController::class, 'index'])->name('proposal-reviews.index');
        Route::patch('/proposals/{proposal}/review', [ProposalReviewController::class, 'update'])->name('proposals.review');
    });
    Route::post('/proposals/{proposal}/submit', [ProposalController::class, 'submit'])->name('proposals.submit');
    Route::resource('services', ServiceController::class);
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::prefix('admin')->middleware('role:administrador')->group(function () {
        Route::patch('/users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
        Route::resource('users', UserController::class)->except('destroy');
    });
});
require __DIR__.'/auth.php';
