<?php

use App\Http\Controllers\Api\BeeFreeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', [BeeFreeController::class, 'index'])->middleware('auth')->name('dashboard');
Route::get('/beefree/data', [BeeFreeController::class, 'credentials'])->middleware('auth', 'throttle:60,1')->name('credentials');
Route::post('/beefree/data/save', [BeeFreeController::class, 'save'])->middleware('auth')->name('save');
Route::get('/beefree/data/check-template', [BeeFreeController::class, 'checkTemplate'])->middleware('auth')->name('check-template');


require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';

