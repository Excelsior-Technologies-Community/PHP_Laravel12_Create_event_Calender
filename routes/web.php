<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EventController;

Route::get('/', [EventController::class, 'index'])->name('calendar');

Route::get('/events', [EventController::class, 'getEvents']);

Route::get('/events/export/csv', [EventController::class, 'exportCsv'])
    ->name('events.export.csv');

Route::get('/events/export/ics/{id}', [EventController::class, 'exportIcsSingle'])
    ->name('events.export.ics.single');

Route::get('/events/reminders/active', [EventController::class, 'getActiveReminders'])
    ->name('events.reminders.active');

Route::post('/events/bulk-action', [EventController::class, 'bulkAction'])
    ->name('events.bulk-action');

Route::post('/events', [EventController::class, 'store']);

Route::put('/events/{id}', [EventController::class, 'update']);

Route::delete('/events/{id}', [EventController::class, 'destroy']);