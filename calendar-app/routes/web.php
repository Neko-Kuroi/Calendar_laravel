<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EventController;

// カレンダー表示用のメインビュー
Route::get('/', function () {
    return view('calendar');
});

// API Routes
Route::prefix('api')->group(function () {
    Route::get('/events', [EventController::class, 'index']);
    Route::post('/events', [EventController::class, 'store']);
    Route::put('/events/{event}', [EventController::class, 'update']);
    Route::delete('/events/{event}', [EventController::class, 'destroy']);
});