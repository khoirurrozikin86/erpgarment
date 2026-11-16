<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\Super\{RoleController, PermissionController, UserManageController};
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LocaleController;


use App\Http\Controllers\Public\{HomeController, PageController, ContactController};


Route::get('/', function () {
    return view('welcome');
});

Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');




// Route::get('/', HomeController::class)->name('home');
// Route::get('/p/{slug}', [PageController::class, 'show'])->name('page.show');
// Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

Route::middleware(['auth', 'verified'])
    ->prefix('super')
    ->name('super.')
    ->group(function () {
        // Keep the old URL while using the dashboard and permission defined in super.php.
        Route::redirect('/dashboard', '/super')->name('dashboard.redirect');
    });



Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


require __DIR__ . '/auth.php';
require __DIR__ . '/super.php';
