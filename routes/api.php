<?php

use App\Http\Controllers\Api\BeeFreeController;
use Illuminate\Support\Facades\Route;

Route::post('/beefree/token', [BeeFreeController::class, 'getToken'])
    ->middleware('auth');
