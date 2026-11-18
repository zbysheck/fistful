<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\WorkController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;

Route::get('/', [WorkController::class, 'home'])->name('home');
Route::get('/search', [WorkController::class, 'search'])->middleware('throttle:60,1')->name('search');
Route::get('/open/{qid}', [WorkController::class, 'open'])->middleware('throttle:30,1')->name('open');
Route::get('/work/{work}', [WorkController::class, 'show'])->name('work.show');
Route::post('/work/{work}/generate', [WorkController::class, 'generate'])->middleware('throttle:10,1')->name('work.generate');

Route::get('/admin/login', [AdminController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AdminController::class, 'login'])->middleware('throttle:5,1');
Route::post('/admin/logout', [AdminController::class, 'logout'])->name('admin.logout');

Route::middleware(EnsureAdmin::class)->prefix('admin')->group(function (): void {
    Route::get('/', [AdminController::class, 'review'])->name('admin.review');
    Route::post('/inspirations', [AdminController::class, 'store'])->name('admin.inspirations.store');
    Route::patch('/inspirations/{inspiration}', [AdminController::class, 'update'])->name('admin.inspirations.update');
    Route::delete('/inspirations/{inspiration}', [AdminController::class, 'destroy'])->name('admin.inspirations.destroy');
    Route::post('/works/{work}/regenerate', [AdminController::class, 'regenerate'])->name('admin.works.regenerate');
});
