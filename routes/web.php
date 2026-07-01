<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EventController;

Route::get('/', [EventController::class, 'index'])->name('calendar');

Route::get('/events', [EventController::class, 'getEvents']);

Route::post('/events', [EventController::class, 'store']);

Route::put('/events/{id}', [EventController::class, 'update']);

Route::delete('/events/{id}', [EventController::class, 'destroy']);