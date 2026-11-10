<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VehicleLookupController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');
Route::middleware('auth')->group(function () {
    Route::post('/vehicles/lookup', VehicleLookupController::class)->middleware(['can:create,App\\Models\\Vehicle', 'throttle:vehicle-lookup'])->name('vehicles.lookup');
    Route::resource('vehicles', VehicleController::class);
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::prefix('admin')->middleware('role:administrador')->group(function () {
        Route::patch('/users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
        Route::resource('users', UserController::class)->except('destroy');
    });
});
require __DIR__.'/auth.php';
