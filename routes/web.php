<?php

use App\Http\Controllers\Api\BeeFreeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', [BeeFreeController::class, 'index'])->name('dashboard');
Route::get('/beefree/data', [BeeFreeController::class, 'credentials'])->middleware('throttle:60,1')->name('credentials');
Route::post('/beefree/data/save', [BeeFreeController::class, 'save'])->name('save');
Route::get('/beefree/data/check-template', [BeeFreeController::class, 'checkTemplate'])->name('check-template');
Route::get('/email-templates', [BeeFreeController::class, 'listTemplates']);

Route::post('/beefree/next', [BeeFreeController::class, 'next'])->name('next');

Route::get('continue/{template}', [BeeFreeController::class, 'showContinue'])->name('continue');

Route::post('/beefree/sendEmail', [BeeFreeController::class, 'sendEmail'])->name('next');

Route::put('/email-templates/{template}', [BeeFreeController::class, 'update']);
Route::delete('/email-templates/{template}', [BeeFreeController::class, 'destroy']);
Route::put('/email-templates/{template}/subject', [BeeFreeController::class, 'updateSubject']);

// Leave out the auth routes
require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
