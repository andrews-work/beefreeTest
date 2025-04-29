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
Route::get('/email-templates', [BeeFreeController::class, 'listTemplates'])->middleware('auth');

Route::post('/beefree/next', [BeeFreeController::class, 'next'])->middleware('auth')->name('next');

Route::get('continue/{template}', [BeeFreeController::class, 'showContinue'])
    ->middleware('auth')
    ->name('continue');

Route::post('/beefree/sendEmail' , [BeeFreeController::class, 'sendEmail'])->middleware('auth')->name('next');

Route::put('/email-templates/{template}', [BeeFreeController::class, 'update'])->middleware('auth');
Route::delete('/email-templates/{template}', [BeeFreeController::class, 'destroy'])->middleware('auth');
Route::put('/email-templates/{template}/subject', [BeeFreeController::class, 'updateSubject'])->middleware('auth');

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';

