<?php
// routes/api/v1/events.php
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\EventDressCodeController;
use App\Http\Controllers\Api\V1\EventLocationController;
use App\Http\Controllers\Api\V1\EventScheduleController;
use Illuminate\Support\Facades\Route;

// Rutas públicas (sin auth)
Route::get('events/{slug}', [EventController::class, 'show']);

// Rutas del master (requieren auth)
Route::middleware(['auth:api', 'user.active', 'user.verified', 'jwt.not_revoked'])->group(function () {
    Route::get('events', [EventController::class, 'index']);
    Route::post('events', [EventController::class, 'store']);
    Route::put('events/{slug}', [EventController::class, 'update']);
    Route::delete('events/{slug}', [EventController::class, 'destroy']);
    Route::get('events/{slug}/modules', [EventController::class, 'getModules']);
    Route::put('events/{slug}/modules', [EventController::class, 'updateModules']);

    Route::get('events/{slug}/schedules', [EventScheduleController::class, 'index']);
    Route::post('events/{slug}/schedules', [EventScheduleController::class, 'store']);
    Route::put('events/{slug}/schedules/{id}', [EventScheduleController::class, 'update']);
    Route::delete('events/{slug}/schedules/{id}', [EventScheduleController::class, 'destroy']);

    Route::get('events/{slug}/locations', [EventLocationController::class, 'index']);
    Route::post('events/{slug}/locations', [EventLocationController::class, 'store']);
    Route::put('events/{slug}/locations/{id}', [EventLocationController::class, 'update']);
    Route::delete('events/{slug}/locations/{id}', [EventLocationController::class, 'destroy']);

    Route::get('events/{slug}/dress-codes', [EventDressCodeController::class, 'index']);
    Route::post('events/{slug}/dress-codes', [EventDressCodeController::class, 'store']);
    Route::put('events/{slug}/dress-codes/{id}', [EventDressCodeController::class, 'update']);
    Route::delete('events/{slug}/dress-codes/{id}', [EventDressCodeController::class, 'destroy']);
});
